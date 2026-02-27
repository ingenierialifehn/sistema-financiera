<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

$duplicateMovId = 24; // The one from procesar_cuadre_asesor.php (based on observation)
$amount = 4600.00;
$agenciaId = 1;
$asesorId = 4; // Luis

try {
    $db->beginTransaction();

    // 1. Verify the duplicate movement exists
    $stmtCheck = $db->prepare("SELECT * FROM movimientos_internos_agencia WHERE id_movimiento_interno = ?");
    $stmtCheck->execute([$duplicateMovId]);
    $mov = $stmtCheck->fetch();

    if (!$mov) {
        throw new Exception("Movimiento $duplicateMovId not found.");
    }

    echo "Deleting duplicate movement: {$mov['observaciones']}\n";

    // 2. Delete the movement
    $stmtDel = $db->prepare("DELETE FROM movimientos_internos_agencia WHERE id_movimiento_interno = ?");
    $stmtDel->execute([$duplicateMovId]);

    // 3. Adjust Agency Box Balance (Subtract the duplicate)
    $stmtBox = $db->prepare("UPDATE cajas_agencias SET saldo_caja_operativa = saldo_caja_operativa - ?, ultima_actualizacion = NOW() WHERE id_agencia = ?");
    $stmtBox->execute([$amount, $agenciaId]);
    echo "Removed $amount from Agency $agenciaId box.\n";

    // 4. Adjust Advisor Virtual Balance (Add back the duplicate deduction, so he owes it? No wait)
    // He delivered 4600 physically.
    // System recorded he delivered 4600 + 4600 = 9200.
    // So system thinks he paid 9200. His debt reduced by 9200.
    // We need to INCREASE his debt (saldo_caja_virtual) by 4600 because one payment was fake.
    $stmtUser = $db->prepare("UPDATE usuarios SET saldo_caja_virtual = saldo_caja_virtual + ? WHERE id_usuario = ?");
    $stmtUser->execute([$amount, $asesorId]);
    echo "Added $amount back to Advisor $asesorId virtual balance (reversed duplicate payment).\n";

    $db->commit();
    echo "Fix applied successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
