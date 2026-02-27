<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID de cuadre requerido');
    }

    $idCuadre = $_GET['id'];
    $db = getDB();

    // 1. Obtener datos del cuadre desde cuadres_asesores
    $stmt = $db->prepare("SELECT c.*, u.username, col.nombre_completo 
                          FROM cuadres_asesores c
                          JOIN usuarios u ON c.id_asesor = u.id_usuario
                          LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                          WHERE c.id = ?");
    $stmt->execute([$idCuadre]);
    $cuadre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuadre) {
        throw new Exception('Cuadre no encontrado');
    }

    $idAsesor = $cuadre['id_asesor'];
    $fechaCuadre = $cuadre['fecha_cuadre'];
    $nombreAsesor = $cuadre['nombre_completo'] ?? $cuadre['username'];

    // 2. Obtener Transacciones (Pagos recibidos ese día por ese asesor)
    // Query idéntico a cuadrar_asesor.php
    $sqlTransacciones = "SELECT c.id, c.monto_pagado, IFNULL(c.capital_cuota, 0) as capital_pagado, (c.monto_pagado - IFNULL(c.capital_cuota, 0)) as interes_pagado, cl.nombre_completo, DATE_FORMAT(c.fecha_pago_real, '%H:%i') as hora
                         FROM cuotas c
                         JOIN prestamos p ON c.prestamo_id = p.id
                         JOIN clientes cl ON p.id_cliente = cl.id
                         WHERE DATE(c.fecha_pago_real) = ?
                         AND c.usuario_cobro_id = ?
                         AND c.estado = 'pagada'
                         ORDER BY c.fecha_pago_real ASC";

    // Nota: Eliminé la restricción de id_agencia en cuotas porque el cuadre ya tiene agencia y el asesor puede cobrar en ruta
    // Aun así, id_agencia en cuotas es del cliente. El asesor puede cobrar a clientes de su agencia.
    // Para ser consistentes con cuadrar_asesor.php, usamos la agencia del cuadre si es necesario, 
    // pero id_asesor y fecha deberían ser suficientes para identificar el cobro.
    // cuadrar_asesor.php usa: AND cl.id_agencia = ? (idAgencia del user session).
    // Aquí usaremos la del cuadre.
    $sqlTransacciones = "SELECT c.id, c.monto_pagado, IFNULL(c.capital_cuota, 0) as capital_pagado, (c.monto_pagado - IFNULL(c.capital_cuota, 0)) as interes_pagado, cl.nombre_completo, DATE_FORMAT(c.fecha_pago_real, '%H:%i') as hora
                         FROM cuotas c
                         JOIN prestamos p ON c.prestamo_id = p.id
                         JOIN clientes cl ON p.id_cliente = cl.id
                         WHERE DATE(c.fecha_pago_real) = ?
                         AND c.usuario_cobro_id = ?
                         AND cl.id_agencia = ?
                         AND c.estado = 'pagada'
                         ORDER BY c.fecha_pago_real ASC";

    $stmtTrans = $db->prepare($sqlTransacciones);
    $stmtTrans->execute([$fechaCuadre, $idAsesor, $cuadre['id_agencia']]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);


    // 3. Obtener Desembolsos Entregados
    $sqlDesembolsos = "SELECT 
                        cl.nombre_completo, 
                        p.monto_capital, 
                        p.numero_prestamo,
                        p.neto_entregar,
                        (p.monto_capital - p.neto_entregar) as monto_anterior
                    FROM prestamos p
                    JOIN clientes cl ON p.id_cliente = cl.id
                    WHERE p.oficial_desembolsos_id = ?
                    AND DATE(p.fecha_desembolso) = ?
                    AND (p.estado = 'Activo' OR p.estado = 'Pagado' OR p.estado = 'Vencido')";
    // Incluimos Pagado/Vencido por si es historico

    $stmtDes = $db->prepare($sqlDesembolsos);
    $stmtDes->execute([$idAsesor, $fechaCuadre]);
    $desembolsos = $stmtDes->fetchAll(PDO::FETCH_ASSOC);


    // 4. Obtener Detalle Bancos
    // Replicamos la lógica de búsqueda por Tag [AID:X]
    $searchTag = "%[AID:$idAsesor]%";
    $searchName = "%" . $nombreAsesor . "%";

    $sqlBancos = "SELECT b.nombre_banco, SUM(mb.monto) as total, mb.descripcion as referencia
                  FROM movimientos_bancarios mb
                  JOIN bancos b ON mb.banco_id = b.id
                  WHERE mb.tipo_transaccion = 'ingreso'
                  AND (mb.descripcion LIKE ? OR mb.descripcion LIKE ?)
                  AND DATE(mb.fecha_hora) = ?
                  GROUP BY b.nombre_banco, mb.descripcion";
    // Agrupamos por descripción también para ver referencias individuales si es posible, 
    // aunque el ticket original agrupa por banco.
    // Cuadrar_asesor.php agrupa por banco.

    $sqlBancosGrouped = "SELECT b.nombre_banco, SUM(mb.monto) as total
                         FROM movimientos_bancarios mb
                         JOIN bancos b ON mb.banco_id = b.id
                         WHERE mb.tipo_transaccion = 'ingreso'
                         AND (mb.descripcion LIKE ? OR mb.descripcion LIKE ?)
                         AND DATE(mb.fecha_hora) = ?
                         GROUP BY b.nombre_banco";

    $stmtBancos = $db->prepare($sqlBancosGrouped);
    $stmtBancos->execute([$searchTag, $searchName, $fechaCuadre]);
    $bancos = $stmtBancos->fetchAll(PDO::FETCH_ASSOC);


    // 5. Estructurar respuesta para el ticket
    session_start();
    $usuarioImprime = $_SESSION['username'] ?? 'Sistema';

    $response = [
        'id_cuadre' => $cuadre['id'],
        'monto_recaudado' => floatval($cuadre['monto_recaudado']),
        'monto_entregado' => floatval($cuadre['monto_entregado']),
        'diferencia' => floatval($cuadre['monto_recaudado']) - floatval($cuadre['monto_entregado']),
        'transacciones' => $transacciones,
        'asesor_nombre' => $nombreAsesor,
        'fecha' => date('d/m/Y H:i:s', strtotime($cuadre['fecha_registro'])), // Fecha de creación del registro
        'total_efectivo_dia' => floatval($cuadre['monto_efectivo']), // Esto en realidad es "efectivo entregado en este cuadre", no total dia.
        // Pero para el ticket, si es reimpresion, mostramos lo guardado.
        'total_banco_dia' => floatval($cuadre['monto_banco']),
        'detalle_bancos' => $bancos,
        'desembolsos_entregados' => $desembolsos,
        'usuario_imprime' => $usuarioImprime
    ];

    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>