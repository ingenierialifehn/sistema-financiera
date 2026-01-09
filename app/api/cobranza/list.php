<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    // Filtros
    $agenciaId = $_GET['agencia_id'] ?? null;
    $asesorId = $_GET['asesor_id'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d'); // Default hoy

    // Modificación ESTRICTA: Solo ver datos de mi usuario
    $asesorId = ($userId && $userId > 0) ? $userId : -1;

    $db = getDB();

    // Query Base: PRESTAMOS ACTIVOS (LEFT JOIN CUOTAS)
    // Para mostrar al cliente aunque no tenga cuota pendiente
    $sql = "SELECT 
            p.id as prestamo_id, p.modalidad, p.monto_capital, 
            cl.nombre_completo as cliente_nombre, cl.id_agencia,
            u.username as asesor_nombre,
            c.id as id, c.numero_cuota, c.monto_cuota, c.fecha_vencimiento, c.estado,
            IF(c.id IS NOT NULL, DATEDIFF(?, c.fecha_vencimiento), 0) as dias_atraso_calc
            FROM prestamos p
            JOIN clientes cl ON p.id_cliente = cl.id
            LEFT JOIN usuarios u ON p.asesor_creditos_id = u.id_usuario
            LEFT JOIN cuotas c ON p.id = c.prestamo_id AND c.estado != 'pagada'
            WHERE p.estado = 'Activo'";

    $params = [$fecha]; // Param 1 para DATEDIFF

    // Filtros
    // Prioridad: Si es MI cliente (Asesor), ignoro agencia para verlo siempre.
    if ($agenciaId && $agenciaId !== 'todas') {
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $agenciaId;
    }

    // Filtro OBLIGATORIO
    $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ? OR cl.cobrador_id = ?)";
    $params[] = $asesorId;
    $params[] = $asesorId;
    $params[] = $asesorId;

    // Ordenar: Primero los que tienen fecha (mora/hoy), al final los sin fecha
    $sql .= " ORDER BY (c.fecha_vencimiento IS NULL), c.fecha_vencimiento ASC, cl.nombre_completo ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Identificar minMap para bloqueo (solo si hay cuotas)
    $minMap = [];
    foreach ($rows as $r) {
        if ($r['id']) { // Si hay cuota
            $pid = $r['prestamo_id'];
            $n = intval($r['numero_cuota']);
            if (!isset($minMap[$pid]) || $n < $minMap[$pid]) {
                $minMap[$pid] = $n;
            }
        }
    }

    // Procesar Datos
    $data = [];
    foreach ($rows as $row) {
        $item = $row;

        if ($row['id']) {
            // TIENE CUOTA PENDIENTE
            $pid = $row['prestamo_id'];
            $n = intval($row['numero_cuota']);

            // Bloqueo
            if (isset($minMap[$pid]) && $n > $minMap[$pid]) {
                $item['bloqueada'] = true;
                $item['motivo_bloqueo'] = 'Debe pagar cuota #' . $minMap[$pid];
            } else {
                $item['bloqueada'] = false;
            }

            // Estado Visual
            $diasDiff = intval($row['dias_atraso_calc']);
            if ($diasDiff > 0) {
                $item['estado_visual'] = 'Mora (' . $diasDiff . ' días)';
                $item['is_mora'] = true;
                $item['categoria'] = 'mora';
            } elseif ($diasDiff == 0) {
                $item['estado_visual'] = 'Vence Hoy';
                $item['is_mora'] = false;
                $item['categoria'] = 'hoy';
            } else {
                $item['estado_visual'] = 'Futuro (' . abs($diasDiff) . ' días)';
                $item['is_mora'] = false;
                $item['categoria'] = 'futuro';
            }
        } else {
            // NO TIENE CUOTAS (Ni pendientes ni pagadas, o todas pagadas)
            // Mostramos el prestamo como informativo
            $item['id'] = 0; // ID falso, no se puede cobrar
            $item['numero_cuota'] = '-';
            $item['monto_cuota'] = '0.00';
            $item['fecha_vencimiento'] = 'N/A';

            $item['bloqueada'] = true;
            $item['motivo_bloqueo'] = 'No hay cuota pendiente generada';

            $item['estado_visual'] = 'Sin Cuotas';
            $item['is_mora'] = false;
            // Lo ponemos en 'futuro' o 'all' para que salga, o creamos categoría 'info'
            // Para que salga en 'Todos', basta con dar una categoría
            $item['categoria'] = 'futuro';
        }

        $data[] = $item;
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>