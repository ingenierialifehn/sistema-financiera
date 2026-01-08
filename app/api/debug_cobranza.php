<?php
require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: text/plain');

$userId = $_SESSION['id_usuario'] ?? 'N/A';
$rol = $_SESSION['rol_nombre'] ?? 'N/A';

echo "=== DIAGNÓSTICO COBRANZA ===\n";
echo "Usuario Logueado ID: $userId\n";
echo "Rol: $rol\n\n";

$db = getDB();

// 1. Prestamos Activos
echo "[Prestamos Activos]\n";
$stmt = $db->query("SELECT id, estado, asesor_creditos_id, oficial_desembolsos_id, id_cliente FROM prestamos WHERE estado = 'Activo'");
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($prestamos)) {
    echo "NO hay préstamos Activos. Verificar estado de préstamos.\n";

    // Ver si hay 'Listo para Entrega' u otros
    $stmtAll = $db->query("SELECT id, estado FROM prestamos LIMIT 5");
    echo "Muestra de estados en BD:\n";
    foreach ($stmtAll->fetchAll() as $p)
        echo "- ID {$p['id']}: {$p['estado']}\n";

} else {
    foreach ($prestamos as $p) {
        echo "- Prestamo #{$p['id']} | Cliente: {$p['id_cliente']} | Asesor ID: {$p['asesor_creditos_id']} | Oficial Desembolso ID: {$p['oficial_desembolsos_id']}\n";

        // Ver cuotas de este prestamo
        $stmtC = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN estado!='pagada' THEN 1 ELSE 0 END) as pendientes FROM cuotas WHERE prestamo_id = {$p['id']}");
        $c = $stmtC->fetch(PDO::FETCH_ASSOC);
        echo "  > Cuotas: Total {$c['total']}, Pendientes {$c['pendientes']}\n";
    }
}

echo "\n[Chequeo de Filtro]\n";
$isAsesor = (stripos($rol, 'Asesor') !== false || stripos($rol, 'Oficial de Credito') !== false || stripos($rol, 'Oficial de Negocios') !== false);
echo "El sistema lo detecta como Asesor con restricción? " . ($isAsesor ? "SI" : "NO") . "\n";
if ($isAsesor) {
    echo "Filtrando por asesor_creditos_id = $userId\n";
}
?>