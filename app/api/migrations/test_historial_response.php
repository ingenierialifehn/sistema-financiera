<?php
/**
 * Script: Ver respuesta exacta de historial_pagos.php
 */

require_once __DIR__ . '/../../config/database.php';

// Simular sesión
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['rol_nombre'] = 'Admin';

$_GET['fecha'] = date('Y-m-d');

// Ejecutar el código de historial_pagos.php
try {
    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    $fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    $isGestor = (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false);
    if ($isGestor) {
        $fechaFiltro = date('Y-m-d');
    }

    $db = getDB();

    $sql = "SELECT 
                c.id as cuota_id, 
                c.numero_cuota, 
                c.monto_pagado, 
                c.fecha_pago_real,
                c.estado,
                cl.nombre_completo, 
                cl.numero_documento,
                p.id as prestamo_id,
                p.modalidad
            FROM cuotas c
            JOIN prestamos p ON c.prestamo_id = p.id
            JOIN clientes cl ON p.id_cliente = cl.id
            WHERE (c.monto_pagado > 0)
              AND DATE(c.fecha_pago_real) = ?";

    $params = [$fechaFiltro];

    if (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false) {
        $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
    }

    if ($agenciaId) {
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY c.fecha_pago_real DESC, c.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['monto_fmt'] = number_format($row['monto_pagado'], 2);
        $row['fecha_fmt'] = date('d/m/Y', strtotime($row['fecha_pago_real']));
    }

    echo "=== Respuesta de historial_pagos.php ===\n\n";
    echo "Total de registros: " . count($data) . "\n\n";

    if (count($data) > 0) {
        echo "Primer registro (estructura):\n";
        print_r($data[0]);

        echo "\n\nJSON que se enviaría al frontend:\n";
        echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
