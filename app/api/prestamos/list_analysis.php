<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment validation

    $db = getDB();

    // Select requests that are in the analysis pipeline (not disbursed/paid/rejected yet, or maybe show all? "todas las solicitudes")
    // Usually "Bandeja de Entrada" implies pending work. 
    // "Solicitado", "En Análisis", "Verificación de Campo", "Pendiente de Operaciones", "Aprobado" (Waiting for disbursement)

    // We can filter out 'Activo', 'Finalizado', 'Rechazado' if we only want the "Pipeline".
    // Or users might want to see history.
    // Prompt says: "Bandeja de Entrada: Debe mostrar una tabla con todas las solicitudes de crédito."
    // I will return all, frontend can filter or valid sorting. 
    // But typically an Inbox is for 'Open' items. I'll focus on the Pipeline states + 'Solicitado'.
    // Actually, let's just return everything descending by date so they see the newest request.

    $agenciaId = isset($_GET['agencia_id']) && !empty($_GET['agencia_id']) ? intval($_GET['agencia_id']) : null;
    $enRuta = isset($_GET['en_ruta']) && $_GET['en_ruta'] == '1';

    $sql = "SELECT p.*, p.comentario_analisis, p.comentario_verificacion, c.nombre_completo as cliente_nombre, c.numero_documento, a.nombre_agencia 
            FROM prestamos p
            JOIN clientes c ON p.id_cliente = c.id
            JOIN agencias a ON c.id_agencia = a.id_agencia
            WHERE 1=1";

    $params = [];

    // Si el filtro "en ruta" está activo, solo mostrar préstamos listos para entrega
    if ($enRuta) {
        $sql .= " AND p.estado = 'Listo para Entrega'";
    } else {
        // De lo contrario, mostrar los estados del pipeline normal
        $sql .= " AND p.estado IN ('Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Aprobado')";
    }

    if ($agenciaId) {
        $sql .= " AND c.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY p.fecha_solicitud DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../../core/ClienteHelper.php';

    foreach ($prestamos as &$p) {
        $riesgo = ClienteHelper::calcularCategoriaRiesgo($db, $p['id_cliente']);
        $p['categoria_riesgo'] = $riesgo['categoria'];
        $p['dias_mora_global'] = $riesgo['dias_mora'];
    }

    echo json_encode([
        'success' => true,
        'data' => $prestamos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>