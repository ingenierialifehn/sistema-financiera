<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$loanId = 12;

try {
    $db->beginTransaction();

    // 1. Update 'pendiente' to 'vencida' for past due dates
    $stmt = $db->prepare("
        UPDATE cuotas 
        SET estado = 'vencida' 
        WHERE prestamo_id = ? 
        AND estado = 'pendiente' 
        AND fecha_vencimiento < CURDATE()
    ");
    $stmt->execute([$loanId]);

    echo "Updated " . $stmt->rowCount() . " installments to 'vencida'.\n";

    // 2. Also check if Today is the due date, maybe status 'pendiente' is fine, or 'por vencer'?
    // Usually 'pendiente' is fine for today.

    $db->commit();

    // Check results
    $stmtCheck = $db->prepare("SELECT numero_cuota, fecha_vencimiento, estado FROM cuotas WHERE prestamo_id = ?");
    $stmtCheck->execute([$loanId]);
    $rows = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "Cuota {$r['numero_cuota']}: {$r['fecha_vencimiento']} - {$r['estado']}\n";
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
