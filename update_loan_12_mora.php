<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$loanId = 12;

try {
    $db->beginTransaction();

    // 1. Get overdue installments
    $stmt = $db->prepare("SELECT id, fecha_vencimiento FROM cuotas WHERE prestamo_id = ? AND estado = 'vencida'");
    $stmt->execute([$loanId]);
    $overdueRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime();

    foreach ($overdueRows as $row) {
        $dueDate = new DateTime($row['fecha_vencimiento']);
        $diff = $today->diff($dueDate);
        $days = $diff->days; // Default is absolute, but verify direction

        // If due date is in the past, days is correct.
        if ($dueDate > $today) {
            $days = 0; // Should not happen for 'vencida' status unless manually set wrong
        }

        $stmtUpd = $db->prepare("UPDATE cuotas SET dias_mora = ? WHERE id = ?");
        $stmtUpd->execute([$days, $row['id']]);

        echo "Updated Cuota ID {$row['id']} (Due: {$row['fecha_vencimiento']}) -> Dias Mora: $days\n";
    }

    $db->commit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
