<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment in production

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Fallback: Si no hay usuario en sesión, usar id 1 (Admin)
    /*
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception("Sesión no válida");
    }
    */
    $idAgencia = $_SESSION['id_agencia'] ?? 1; // Default to 1 if not set

    $db = getDB();

    // 1. Get total Net Amount to be Disbursed
    // We sum 'Pendiente de Operaciones' AND 'Aprobado'.
    // We join 'clientes' because 'prestamos' table might not have 'id_agencia' column directly.
    $stmt = $db->prepare("SELECT SUM(IFNULL(p.neto_entregar, p.monto_capital)) as total_requerido 
                          FROM prestamos p
                          JOIN clientes c ON p.id_cliente = c.id
                          WHERE c.id_agencia = ? 
                          AND p.estado IN ('Pendiente de Operaciones', 'Aprobado')");
    $stmt->execute([$idAgencia]);
    $totalRequerido = floatval($stmt->fetchColumn() ?: 0);

    // 2. Get current money in Agency Vault (Boveda)
    // Based on get_saldo_sistema.php, the columns are 'saldo_caja_operativa' (Caja) and 'saldo_efectivo' (Boveda)
    $stmtBoveda = $db->prepare("SELECT saldo_efectivo, saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
    $stmtBoveda->execute([$idAgencia]);
    $boveda = $stmtBoveda->fetch(PDO::FETCH_ASSOC);

    $saldoBoveda = floatval($boveda['saldo_efectivo'] ?: 0);
    $saldoCaja = floatval($boveda['saldo_caja_operativa'] ?: 0);

    // 3. Calculate Suggested Amount to Pull from Bank
    // Suggested = Total Needed - (System Vault + System Cash)
    // If we have enough in Vault+Cash, we don't need to pull from bank.
    $disponibleTotal = $saldoBoveda + $saldoCaja;
    $sugerido = $totalRequerido - $disponibleTotal;

    if ($sugerido < 0)
        $sugerido = 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'total_requerido' => $totalRequerido,
            'saldo_boveda' => $saldoBoveda,
            'saldo_caja' => $saldoCaja,
            'monto_sugerido' => $sugerido
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
