<?php
// HARDCORE DEBUGGING wrapper
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep json clean
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../debug_asesores.log');

require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

function logMsg($msg)
{
    file_put_contents(__DIR__ . '/../../../debug_asesores.log', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

try {
    logMsg("Iniciando Request");

    $agenciaId = $_SESSION['id_agencia'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    logMsg("Agencia ID Session: " . ($agenciaId ?? 'NULL'));

    if (!$agenciaId) {
        $agenciaId = $_GET['id_agencia'] ?? null;
        logMsg("Agencia ID GET: " . ($agenciaId ?? 'NULL'));
    }

    if (!$agenciaId) {
        // Fix temporal: Si es admin y no tiene agencia, buscar la primera agencia para testear
        // O lanzar error.
        logMsg("ERROR: No agencia found.");
        throw new Exception("Usuario no asignado a una agencia.");
    }

    $db = getDB();

    // 1. Obtener TODOS los usuarios activos de la agencia
    // CORRECCION: El nombre esta en la tabla `colaboradores`, no en `usuarios`.
    $sql = "SELECT u.id_usuario, u.username, 
                   COALESCE(c.nombre_completo, u.username) as nombre_completo,
                   c.id_agencia
            FROM usuarios u
            LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
            WHERE (u.id_agencia = ? OR c.id_agencia = ?) 
              AND u.estado = 'activo'
            ORDER BY nombre_completo ASC";

    logMsg("SQL Users: Select JOIN colaboradores params: $agenciaId, $agenciaId");

    $stmt = $db->prepare($sql);
    $stmt->execute([$agenciaId, $agenciaId]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logMsg("Usuarios encontrados: " . count($usuarios));

    $asesores = [];

    foreach ($usuarios as $u) {
        $uid = $u['id_usuario'];
        $nombreCompleto = $u['nombre_completo']; // Ya viene limpio del SQL

        // A. Total Recaudado (Cartera asignada)
        $sqlCobro = "SELECT IFNULL(SUM(c.monto_pagado), 0) 
                     FROM cuotas c
                     JOIN prestamos p ON c.prestamo_id = p.id
                     WHERE (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)
                       AND DATE(c.fecha_pago_real) = ?";

        $stmtC = $db->prepare($sqlCobro);
        $stmtC->execute([$uid, $uid, $fecha]);
        $totalCobrado = floatval($stmtC->fetchColumn());

        // B. Total Ya Entregado (Heurística por nombre en descripción)
        $searchPattern = "%" . $nombreCompleto . "%";

        $sqlEfvo = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_caja 
                    WHERE id_caja IN (SELECT id FROM cajas_agencias WHERE id_agencia = ?) 
                    AND categoria = 'Cuadre Asesor' 
                    AND descripcion LIKE ? 
                    AND DATE(fecha_movimiento) = ?";
        $stmtE = $db->prepare($sqlEfvo);
        $stmtE->execute([$agenciaId, $searchPattern, $fecha]);
        $entregadoEfvo = floatval($stmtE->fetchColumn());

        $sqlBanco = "SELECT IFNULL(SUM(monto), 0) FROM movimientos_bancarios 
                     WHERE tipo_transaccion = 'deposito' 
                     AND descripcion LIKE ? 
                     AND DATE(fecha_movimiento) = ?";
        $stmtB = $db->prepare($sqlBanco);
        $stmtB->execute([$searchPattern, $fecha]);
        $entregadoBanco = floatval($stmtB->fetchColumn());

        $totalEntregado = $entregadoEfvo + $entregadoBanco;
        $pendiente = $totalCobrado - $totalEntregado;

        $asesores[] = [
            'id_usuario' => $uid,
            'nombre_completo' => $nombreCompleto,
            'recaudado_hoy' => $totalCobrado,
            'entregado_hoy' => $totalEntregado,
            'pendiente' => $pendiente
        ];
    }

    logMsg("Enviando " . count($asesores) . " asesores.");

    echo json_encode(['success' => true, 'data' => $asesores]);

} catch (Exception $e) {
    logMsg("Excepcion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>