<?php
require_once __DIR__ . '/../config/database.php';
session_start();
header('Content-Type: text/plain');

$userId = $_SESSION['id_usuario'] ?? 0;
$userName = $_SESSION['usuario'] ?? 'Anon';

echo "=== DIAGNÓSTICO DE CARTERA ($userName - ID $userId) ===\n\n";

$db = getDB();

// 1. Prestamos ACTIVOS asignados al usuario
$sql = "SELECT p.id, p.monto_capital, p.estado, c.nombre_completo 
        FROM prestamos p 
        JOIN clientes c ON p.id_cliente = c.id
        WHERE p.estado = 'Activo' 
        AND (p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";

$stmt = $db->prepare($sql);
$stmt->execute([$userId, $userId]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($loans)) {
    echo "NO tienes préstamos activos asignados. (Por eso no ves nada).\n";
    echo "Verifica si siguen como 'Ajenos' en fix_assignments.php.\n";
} else {
    echo "Tienes " . count($loans) . " préstamos activos en tu cartera:\n";
    foreach ($loans as $l) {
        echo "\n[Prestamo #{$l['id']} - {$l['nombre_completo']}]\n";

        // 2. Verificar Cuotas
        $stmtC = $db->query("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = {$l['id']}");
        $count = $stmtC->fetchColumn();

        echo "  > Estado: {$l['estado']}\n";
        echo "  > Cuotas Generadas: $count\n";

        if ($count == 0) {
            echo "  > ⚠️ ALERTA: Este préstamo está ACTIVO pero NO TIENE CUOTAS. No aparecerá en cobranza.\n";
        } else {
            // Verificar pendientes
            $stmtP = $db->query("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = {$l['id']} AND estado != 'pagada'");
            $pend = $stmtP->fetchColumn();
            echo "  > Cuotas Pendientes: $pend\n";
        }
    }
}
?>