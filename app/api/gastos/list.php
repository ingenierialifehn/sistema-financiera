<?php
require_once '../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

header('Content-Type: application/json');

try {
    // 1. Validar Autenticación
    Auth::requireAuth();
    $user = Auth::checkSession();

    // 2. Obtener Parámetros de Filtrado
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    // 3. Conexión a Base de Datos
    $db = getDB();

    // 4. Construir Consulta
    // Seleccionamos movimientos que corresponden a gastos (filtrando por categorías conocidas o excluyendo ingresos)
    // Categorías usadas: Planilla, Luz, Agua, Alquiler, Internet, Materiales, Otros
    // OJO: 'Recaudo Asesor' es un ingreso, 'Retiro' o 'Deposito' podrían ser caja.
    // Asumiremos que todo lo que no sea 'Recaudo Asesor' es gasto si estamos en este contexto, 
    // pero para ser más seguros usaremos el IN con las categorías.

    $categoriasGastos = ['Planilla', 'Luz', 'Agua', 'Alquiler', 'Internet', 'Materiales', 'Otros'];

    // Crear string de placeholders para el IN (?,?,...)
    $inQuery = implode(',', array_fill(0, count($categoriasGastos), '?'));

    $sql = "SELECT 
                m.id_movimiento_interno as id,
                m.fecha_movimiento,
                m.tipo_movimiento as categoria,
                m.monto,
                m.observaciones,
                a.nombre_agencia,
                u.username as usuario_registro
            FROM movimientos_internos_agencia m
            JOIN agencias a ON m.id_agencia = a.id_agencia
            JOIN usuarios u ON m.id_usuario_operador = u.id_usuario
            WHERE DATE(m.fecha_movimiento) BETWEEN ? AND ?
            AND m.tipo_movimiento IN ($inQuery)";

    $params = [$fechaDesde, $fechaHasta, ...$categoriasGastos];

    if ($agenciaId) {
        $sql .= " AND m.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY m.fecha_movimiento DESC";

    // 5. Ejecutar Consulta
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Formatear Respuesta
    // Formatear montos y fechas si es necesario, o enviarlos raw
    foreach ($gastos as &$gasto) {
        $gasto['monto'] = floatval($gasto['monto']);
        // Extra: Podríamos limpiar observaciones si tienen prefijos
    }

    echo json_encode([
        'success' => true,
        'data' => $gastos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
