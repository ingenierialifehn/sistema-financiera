<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    $userId = $_SESSION['id_usuario'] ?? 0;
    $rol = $_SESSION['rol_nombre'] ?? '';

    // Filtros
    $fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? null;

    // Seguridad: Gestores solo ven HOY
    $isGestor = (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false);
    if ($isGestor) {
        $fechaFiltro = date('Y-m-d');
    }

    $db = getDB();

    // Consulta: Cuotas pagadas o parciales
    // Se asume que fecha_pago_real indica cuándo se recibió el dinero
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

    // Filtros de Rol (Asesor ve solo lo suyo)
    if (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial') !== false) {
        $sql .= " AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
    }

    // Filtro Agencia
    // Filtro Agencia (Lógica de Privacidad)
    $canViewAll = (stripos($rol, 'Administrador') !== false || stripos($rol, 'Gerente') !== false);

    if (!$canViewAll) {
        // Rol Operativo: Forzar Agencia de Sesión
        $sessionAgencia = $_SESSION['id_agencia'] ?? 0;
        $sql .= " AND cl.id_agencia = ?";
        $params[] = $sessionAgencia;
    } else {
        // Rol Gerencial: Permitir filtro opcional
        if ($agenciaId && $agenciaId !== 'todas') {
            $sql .= " AND cl.id_agencia = ?";
            $params[] = $agenciaId;
        }
    }

    $sql .= " ORDER BY c.fecha_pago_real DESC, c.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formateo
    foreach ($data as &$row) {
        // Campos originales
        $row['monto_fmt'] = number_format($row['monto_pagado'], 2);
        $row['fecha_fmt'] = date('d/m/Y H:i', strtotime($row['fecha_pago_real']));

        // Aliases para compatibilidad con el frontend
        $row['id'] = $row['cuota_id'];  // Para el ticket
        $row['fecha_hora'] = $row['fecha_pago_real'];  // Para mostrar
        $row['cliente'] = $row['nombre_completo'];  // Para mostrar
        $row['monto'] = $row['monto_pagado'];  // Para mostrar
        $row['concepto'] = "Cuota #{$row['numero_cuota']} - Préstamo #{$row['prestamo_id']}";
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>