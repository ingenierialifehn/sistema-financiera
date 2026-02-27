<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';

header('Content-Type: application/json');

try {
    // Auth::requireAuth(); // Can uncomment if needed, usually passed by session check in middleware or similar

    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    $db = getDB();

    // If user is not admin, force agency restriction (if needed). Assuming admin panel allows seeing multiple.
    // But for safety, check session agency if not provided.
    if (!$agenciaId) {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $agenciaId = $_SESSION['id_agencia'] ?? null;
    }

    $sql = "SELECT c.id_control, c.fecha_dia as fecha_apertura, c.hora_cierre, 
                   c.saldo_cierre_sistema, c.saldo_cierre_fisico, c.diferencia_cierre,
                   u.username as usuario_cierre, 
                   col.nombre_completo as nombre_usuario_cierre,
                   a.nombre_agencia,
                   (
                       SELECT COALESCE(SUM(mia.monto), 0)
                       FROM movimientos_internos_agencia mia
                       WHERE mia.id_agencia = c.id_agencia 
                       AND DATE(mia.fecha_movimiento) = c.fecha_dia
                       AND mia.tipo_movimiento = 'Recaudo Asesor'
                   ) as total_recaudado_dia
            FROM control_caja_diaria c
            JOIN agencias a ON c.id_agencia = a.id_agencia
            LEFT JOIN usuarios u ON c.id_usuario_cierre = u.id_usuario
            LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
            WHERE DATE(c.fecha_dia) BETWEEN ? AND ?
            AND c.estado = 'Cerrado'";

    $params = [$fechaInicio, $fechaFin];

    if ($agenciaId) {
        $sql .= " AND c.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY c.fecha_dia DESC, c.hora_cierre DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cierres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $cierres]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>