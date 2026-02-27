<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$loanId = 12;
$newDisbursementDate = '2025-12-19';
$intervalDays = 14;

try {
    $db->beginTransaction();

    // 1. Update Loan Disbursement Date is already done, but no harm repeating
    $stmtUpdateLoan = $db->prepare("UPDATE prestamos SET fecha_desembolso = ? WHERE id = ?");
    $stmtUpdateLoan->execute([$newDisbursementDate, $loanId]);

    // 2. Get existing installments
    $stmtGetCuotas = $db->prepare("SELECT id, numero_cuota, fecha_vencimiento FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC, id ASC");
    $stmtGetCuotas->execute([$loanId]);
    $cuotas = $stmtGetCuotas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Recalculate dates correctly
    $currentDate = new DateTime($newDisbursementDate);
    $lastNumeroCuota = 0;

    // We need to keep a 'running date' that only increments when the loan installment number increments

    foreach ($cuotas as $cuota) {
        $numeroCuota = $cuota['numero_cuota'];

        if ($numeroCuota > $lastNumeroCuota) {
            // New installment number, advance the date
            // Note: If numbering is sequential (1, 2, 3), one jump per step.
            // If there's a gap (unlikely), we might want to respect it, but generally just +interval.
            $currentDate->modify("+$intervalDays days");
            $lastNumeroCuota = $numeroCuota;
        }
        // If numeroCuota == lastNumeroCuota (split payment), we use the SAME currentDate

        $newDateStr = $currentDate->format('Y-m-d');

        $stmtUpdateCuota = $db->prepare("UPDATE cuotas SET fecha_vencimiento = ? WHERE id = ?");
        $stmtUpdateCuota->execute([$newDateStr, $cuota['id']]);

        echo "Cuota {$cuota['numero_cuota']} (ID {$cuota['id']}): New Date $newDateStr\n";
    }

    $db->commit();
    echo "Successfully updated loan schedule with correct split handling.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
