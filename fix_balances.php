<?php
// fix_balances.php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

try {
    $db->beginTransaction();

    // 1. Identificar agencias con saldo en la tabla "vieja" (agencias)
    $stmt = $db->query("SELECT id_agencia, saldo_efectivo FROM agencias");
    $agencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $migrados = 0;

    foreach ($agencias as $ag) {
        $idAgencia = $ag['id_agencia'];
        $saldoAgencia = floatval($ag['saldo_efectivo']);

        // Verificar registro en cajas_agencias
        $stmtCaja = $db->prepare("SELECT saldo_efectivo FROM cajas_agencias WHERE id_agencia = ?");
        $stmtCaja->execute([$idAgencia]);
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

        if (!$caja) {
            // Crear registro si no existe, transfiriendo el saldo
            $stmtInsert = $db->prepare("INSERT INTO cajas_agencias (id_agencia, saldo_efectivo, saldo_caja_operativa, created_at) VALUES (?, ?, 0.00, NOW())");
            $stmtInsert->execute([$idAgencia, $saldoAgencia]);
            $migrados++;
        } else {
            // Si existe pero está en 0 y agencias tiene saldo, actualizar
            $saldoCaja = floatval($caja['saldo_efectivo']);
            if ($saldoCaja == 0 && $saldoAgencia > 0) {
                $stmtUpdate = $db->prepare("UPDATE cajas_agencias SET saldo_efectivo = ? WHERE id_agencia = ?");
                $stmtUpdate->execute([$saldoAgencia, $idAgencia]);
                $migrados++;
            }
        }
    }

    $db->commit();
    echo "Sincronización completada. Agencias procesadas: " . count($agencias) . ". Actualizaciones: $migrados";

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    echo "Error: " . $e->getMessage();
}
