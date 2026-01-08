<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data)
        $data = $_POST;

    if (empty($data['prestamo_id']) || empty($data['nuevo_estado'])) {
        throw new Exception("ID de préstamo y nuevo estado son obligatorios");
    }

    $prestamoId = intval($data['prestamo_id']);
    $nuevoEstado = $data['nuevo_estado'];
    $validStates = ['Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado', 'Rechazado', 'Listo para Entrega'];

    if (!in_array($nuevoEstado, $validStates)) {
        throw new Exception("Estado inválido");
    }

    $db = getDB();

    // Start transaction
    $db->beginTransaction();

    try {
        // If changing to 'Listo para Entrega', deduct from cash
        if ($nuevoEstado === 'Listo para Entrega') {
            // Get loan details
            $stmtLoan = $db->prepare("SELECT p.*, c.id_agencia 
                                      FROM prestamos p
                                      JOIN clientes c ON p.id_cliente = c.id
                                      WHERE p.id = ?");
            $stmtLoan->execute([$prestamoId]);
            $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                throw new Exception("Préstamo no encontrado");
            }

            $netoEntregar = $loan['neto_entregar'] ?? $loan['monto_capital'];
            $agenciaId = $loan['id_agencia'];

            // Get agency cash box balance
            $stmtCaja = $db->prepare("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
            $stmtCaja->execute([$agenciaId]);
            $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

            if (!$caja) {
                throw new Exception("Caja de agencia no encontrada");
            }

            $saldoActual = floatval($caja['saldo_caja_operativa']);

            if ($saldoActual < $netoEntregar) {
                throw new Exception("Fondos insuficientes en caja. Disponible: L " . number_format($saldoActual, 2) . ", Requerido: L " . number_format($netoEntregar, 2));
            }

            // Deduct from cash
            $stmtUpdate = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa - ? WHERE id_agencia = ?");
            $stmtUpdate->execute([$netoEntregar, $agenciaId]);

            // Log the movement
            // Get current user ID
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['id_usuario'] ?? 1; // Fallback to 1 if not in session

            $stmtLog = $db->prepare("INSERT INTO movimientos_internos_agencia 
                                     (id_agencia, id_usuario_operador, tipo_movimiento, monto, observaciones, fecha_movimiento) 
                                     VALUES (?, ?, 'Caja a Ruta', ?, ?, NOW())");
            $stmtLog->execute([
                $agenciaId,
                $userId,
                $netoEntregar,
                "Desembolso préstamo #$prestamoId - Cliente: " . $loan['id_cliente']
            ]);
        }

        // Update loan status
        $stmt = $db->prepare("UPDATE prestamos SET estado = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevoEstado, $prestamoId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Estado actualizado a '$nuevoEstado'" . ($nuevoEstado === 'Listo para Entrega' ? '. Fondos reservados en caja.' : '')
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>