<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $asesorId = $_GET['asesor_id'] ?? null;

    $db = getDB();

    $sql = "SELECT c.*, u.username, col.nombre_completo 
            FROM cuadres_asesores c
            JOIN usuarios u ON c.id_asesor = u.id_usuario
            LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
            WHERE c.fecha_cuadre BETWEEN ? AND ?";
    
    $params = [$fechaInicio, $fechaFin];

    if ($asesorId) {
        $sql .= " AND c.id_asesor = ?";
        $params[] = $asesorId;
    }

    $sql .= " ORDER BY c.fecha_registro DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cuadres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $cuadres]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
