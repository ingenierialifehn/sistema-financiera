<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión (Administrador)
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    $agenciaId = $_GET['agencia_id'] ?? 'todas';
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD');
    }

    if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
        throw new Exception('La fecha inicial no puede ser mayor que la fecha final');
    }

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Determinar filtro de agencia
    $filtroAgenciaSql = "";
    $paramsResumen = [$fechaDesde, $fechaHasta];
    $paramsDetalle = [$fechaDesde, $fechaHasta];
    $paramsModalidad = [$fechaDesde, $fechaHasta];

    $nombreAgencia = "CONSOLIDADO (TODAS LAS AGENCIAS)";

    if ($agenciaId !== 'todas' && is_numeric($agenciaId)) {
        $filtroAgenciaSql = "AND c.id_agencia = ?";
        $paramsResumen[] = $agenciaId;
        $paramsDetalle[] = $agenciaId;
        $paramsModalidad[] = $agenciaId;

        $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
        $stmt->execute([$agenciaId]);
        $nombreAgencia = $stmt->fetchColumn() ?: "Agencia Desconocida";
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
                   $filtroAgenciaSql
                   AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                   AND p.fecha_desembolso IS NOT NULL";

    $stmtResumen = $db->prepare($sqlResumen);
    $stmtResumen->execute($paramsResumen);
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
                   p.tipo_prestamo,
                   ag.nombre_agencia,
                   COALESCE(col.nombre_completo, u.username, 'N/A') as oficial_desembolso
                   FROM prestamos p
                   INNER JOIN clientes c ON p.id_cliente = c.id
                   LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                   LEFT JOIN usuarios u ON p.oficial_desembolsos_id = u.id_usuario
                   LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                   WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                   $filtroAgenciaSql
                   AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                   AND p.fecha_desembolso IS NOT NULL
                   ORDER BY p.fecha_desembolso DESC";

    $stmtDetalle = $db->prepare($sqlDetalle);
    $stmtDetalle->execute($paramsDetalle);
    $desembolsos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

    foreach ($desembolsos as &$desembolso) {
        $desembolso['monto_capital'] = round(floatval($desembolso['monto_capital']), 2);
        $desembolso['total_a_pagar'] = round(floatval($desembolso['total_a_pagar']), 2);
        $desembolso['tasa_total'] = round(floatval($desembolso['tasa_total']), 2);
        $desembolso['plazo_meses'] = intval($desembolso['plazo_meses']);
        $desembolso['agencia'] = $desembolso['nombre_agencia'] ?? 'N/A';
    }

    // RESUMEN POR MODALIDAD
    $sqlModalidad = "SELECT 
                     modalidad,
                     COUNT(*) as cantidad,
                     IFNULL(SUM(monto_capital), 0) as monto_total
                     FROM prestamos p
                     INNER JOIN clientes c ON p.id_cliente = c.id
                     WHERE DATE(p.fecha_desembolso) BETWEEN ? AND ?
                     $filtroAgenciaSql
                     AND p.estado IN ('Activo', 'Finalizado', 'Refinanciado')
                     AND p.fecha_desembolso IS NOT NULL
                     GROUP BY p.modalidad
                     ORDER BY cantidad DESC";

    $stmtModalidad = $db->prepare($sqlModalidad);
    $stmtModalidad->execute($paramsModalidad);
    $porModalidad = $stmtModalidad->fetchAll(PDO::FETCH_ASSOC);

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
            'id_agencia' => $agenciaId,
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
        'error_detail' => 'Error en consolidado_desembolsos.php'
    ], JSON_PRETTY_PRINT);
}
?>