<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Obtener id_agencia de la sesión
    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia) {
        throw new Exception('No se pudo determinar la agencia del usuario');
    }

    // Obtener fechas del filtro
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01'); // Primer día del mes actual
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d'); // Hoy

    $db = getDB();

    // TOTAL DE PRÉSTAMOS DESEMBOLSADOS EN EL PERIODO
    $sqlResumen = "SELECT 
                   COUNT(*) as cantidad_prestamos,
                   IFNULL(SUM(monto_capital), 0) as monto_total_colocado,
                   IFNULL(AVG(monto_capital), 0) as promedio_prestamo
                   FROM prestamos p
                   JOIN clientes c ON p.id_cliente = c.id
                   WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                   AND c.id_agencia = ?
                   AND p.estado IN ('Activo', 'Finalizado')";

    $stmtResumen = $db->prepare($sqlResumen);
    $stmtResumen->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    // DETALLE DE DESEMBOLSOS
    $sqlDetalle = "SELECT 
                   p.id,
                   p.fecha_desembolso,
                   c.nombre_completo,
                   c.numero_documento,
                   p.monto_capital,
                   p.modalidad,
                   p.plazo_meses,
                   p.tasa_total,
                   p.total_a_pagar,
                   p.estado,
                   COALESCE(col.nombre_completo, u.username, 'N/A') as oficial_desembolso
                   FROM prestamos p
                   JOIN clientes c ON p.id_cliente = c.id
                   LEFT JOIN usuarios u ON p.oficial_desembolsos_id = u.id_usuario
                   LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                   WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                   AND c.id_agencia = ?
                   AND p.estado IN ('Activo', 'Finalizado')
                   ORDER BY p.fecha_desembolso DESC";

    $stmtDetalle = $db->prepare($sqlDetalle);
    $stmtDetalle->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $desembolsos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

    // RESUMEN POR MODALIDAD
    $sqlModalidad = "SELECT 
                     modalidad,
                     COUNT(*) as cantidad,
                     IFNULL(SUM(monto_capital), 0) as monto_total
                     FROM prestamos p
                     JOIN clientes c ON p.id_cliente = c.id
                     WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                     AND c.id_agencia = ?
                     AND p.estado IN ('Activo', 'Finalizado')
                     GROUP BY p.modalidad
                     ORDER BY cantidad DESC";

    $stmtModalidad = $db->prepare($sqlModalidad);
    $stmtModalidad->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $porModalidad = $stmtModalidad->fetchAll(PDO::FETCH_ASSOC);

    // Obtener nombre de la agencia
    $sqlAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtAgencia = $db->prepare($sqlAgencia);
    $stmtAgencia->execute([$idAgencia]);
    $nombreAgencia = $stmtAgencia->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'agencia' => $nombreAgencia,
            'resumen' => $resumen,
            'desembolsos' => $desembolsos,
            'por_modalidad' => $porModalidad
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>