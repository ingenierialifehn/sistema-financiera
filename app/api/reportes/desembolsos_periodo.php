<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión y obtener id_agencia
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia || empty($idAgencia)) {
        throw new Exception('No se pudo determinar la agencia del usuario. Verifique su perfil.');
    }

    // Obtener fechas del filtro con validación
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01'); // Primer día del mes actual
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d'); // Hoy

    // Validar formato de fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD');
    }

    // Validar que fecha_desde no sea mayor que fecha_hasta
    if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
        throw new Exception('La fecha inicial no puede ser mayor que la fecha final');
    }

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Verificar que la agencia existe
    $sqlVerifAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtVerif = $db->prepare($sqlVerifAgencia);
    $stmtVerif->execute([$idAgencia]);
    $nombreAgencia = $stmtVerif->fetchColumn();

    if (!$nombreAgencia) {
        throw new Exception('La agencia asignada no existe en el sistema');
    }

    // TOTAL DE PRÉSTAMOS DESEMBOLSADOS EN EL PERIODO
    $sqlResumen = "SELECT 
                   COUNT(*) as cantidad_prestamos,
                   IFNULL(SUM(monto_capital), 0) as monto_total_colocado,
                   SUM(CASE WHEN tipo_prestamo = 'Refinanciamiento' THEN 1 ELSE 0 END) as cantidad_refinanciamientos,
                   SUM(CASE WHEN tipo_prestamo != 'Refinanciamiento' OR tipo_prestamo IS NULL THEN 1 ELSE 0 END) as cantidad_nuevos
                   FROM prestamos p
                   INNER JOIN clientes c ON p.id_cliente = c.id
                   WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                   AND c.id_agencia = ?
                   AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                   AND p.fecha_desembolso IS NOT NULL";

    $stmtResumen = $db->prepare($sqlResumen);
    $stmtResumen->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    // DETALLE DE DESEMBOLSOS - Mejorado con más información
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
                   p.tipo_prestamo,
                   COALESCE(col.nombre_completo, u.username, 'N/A') as oficial_desembolso
                   FROM prestamos p
                   INNER JOIN clientes c ON p.id_cliente = c.id
                   LEFT JOIN usuarios u ON p.oficial_desembolsos_id = u.id_usuario
                   LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                   WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                   AND c.id_agencia = ?
                   AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                   AND p.fecha_desembolso IS NOT NULL
                   ORDER BY p.fecha_desembolso DESC";

    $stmtDetalle = $db->prepare($sqlDetalle);
    $stmtDetalle->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $desembolsos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

    // Formatear valores numéricos
    foreach ($desembolsos as &$desembolso) {
        $desembolso['monto_capital'] = round(floatval($desembolso['monto_capital']), 2);
        $desembolso['total_a_pagar'] = round(floatval($desembolso['total_a_pagar']), 2);
        $desembolso['tasa_total'] = round(floatval($desembolso['tasa_total']), 2);
        $desembolso['plazo_meses'] = intval($desembolso['plazo_meses']);
    }

    // RESUMEN POR MODALIDAD
    $sqlModalidad = "SELECT 
                     modalidad,
                     COUNT(*) as cantidad,
                     IFNULL(SUM(monto_capital), 0) as monto_total
                     FROM prestamos p
                     INNER JOIN clientes c ON p.id_cliente = c.id
                     WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                     AND c.id_agencia = ?
                     AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                     AND p.fecha_desembolso IS NOT NULL
                     GROUP BY p.modalidad
                     ORDER BY cantidad DESC";

    $stmtModalidad = $db->prepare($sqlModalidad);
    $stmtModalidad->execute([$fechaDesde, $fechaHasta, $idAgencia]);
    $porModalidad = $stmtModalidad->fetchAll(PDO::FETCH_ASSOC);

    // Formatear valores
    foreach ($porModalidad as &$modalidad) {
        $modalidad['monto_total'] = round(floatval($modalidad['monto_total']), 2);
        $modalidad['cantidad'] = intval($modalidad['cantidad']);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'agencia' => $nombreAgencia,
            'id_agencia' => $idAgencia,
            'resumen' => [
                'cantidad_prestamos' => intval($resumen['cantidad_prestamos']),
                'monto_total_colocado' => round(floatval($resumen['monto_total_colocado']), 2),
                'cantidad_nuevos' => intval($resumen['cantidad_nuevos']),
                'cantidad_refinanciamientos' => intval($resumen['cantidad_refinanciamientos'])
            ],
            'desembolsos' => $desembolsos,
            'por_modalidad' => $porModalidad
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => 'Error en desembolsos_periodo.php'
    ], JSON_PRETTY_PRINT);
}
?>