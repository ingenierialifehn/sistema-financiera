<?php
/**
 * API: Obtener próximos desembolsos (créditos aprobados) de la agencia
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('operaciones');

$db = getDB();
$user = Auth::getCurrentUser();

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    // Obtener préstamos aprobados de la agencia
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.numero_prestamo,
            p.monto_prestado,
            p.tasa_interes,
            p.periodo_meses,
            p.monto_total,
            p.monto_cuota,
            p.fecha_desembolso,
            p.created_at,
            c.nombre_completo as cliente_nombre,
            c.numero_documento as cliente_dni,
            c.telefono as cliente_telefono
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id_agencia = ? AND p.estado = 'aprobado'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$idAgencia]);
    $desembolsos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success([
        'desembolsos' => $desembolsos
    ]);

} catch (Exception $e) {
    error_log("Error en get_proximos_desembolsos.php: " . $e->getMessage());
    Response::serverError('Error al obtener próximos desembolsos');
}
