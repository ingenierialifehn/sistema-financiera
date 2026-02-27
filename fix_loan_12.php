<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$loanId = 12;
$newDisbursementDate = '2025-12-19';
$intervalDays = 14;

try {
    $db->beginTransaction();

    // 1. Update Loan Disbursement Date
    $stmtUpdateLoan = $db->prepare("UPDATE prestamos SET fecha_desembolso = ? WHERE id = ?");
    $stmtUpdateLoan->execute([$newDisbursementDate, $loanId]);
    echo "Updated Loan $loanId disbursement date to $newDisbursementDate.\n";

    // 2. Get existing installments
    $stmtGetCuotas = $db->prepare("SELECT id, numero_cuota, fecha_vencimiento FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
    $stmtGetCuotas->execute([$loanId]);
    $cuotas = $stmtGetCuotas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Recalculate dates
    $currentDate = new DateTime($newDisbursementDate);

    foreach ($cuotas as $cuota) {
        $currentDate->modify("+$intervalDays days");
        $newDateStr = $currentDate->format('Y-m-d');

        $stmtUpdateCuota = $db->prepare("UPDATE cuotas SET fecha_vencimiento = ? WHERE id = ?");
        $stmtUpdateCuota->execute([$newDateStr, $cuota['id']]);

        echo "Cuota {$cuota['numero_cuota']}: Old Date {$cuota['fecha_vencimiento']} -> New Date $newDateStr\n";
    }

    $db->commit();
    echo "Successfully updated loan schedule.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
