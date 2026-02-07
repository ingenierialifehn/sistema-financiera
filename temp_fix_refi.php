<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "Iniciando corrección de neto_entregar...\n";

$stmt = $db->query("SELECT * FROM prestamos WHERE tipo_prestamo IN ('Refinanciamiento', 'Readecuacion', 'Represtamo') AND estado IN ('Pendiente de Operaciones', 'Aprobado', 'Solicitado', 'En Análisis')");
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($loans as $loan) {
    // Find prev loan
    $stmtPrev = $db->prepare("SELECT id, monto_capital, 
                                (SELECT IFNULL(SUM(monto_pagado * (capital_cuota/monto_cuota)), 0) 
                                 FROM cuotas WHERE prestamo_id = p_prev.id AND estado IN ('pagada', 'parcial') AND monto_cuota > 0) as amortizado
                              FROM prestamos p_prev 
                              WHERE id_cliente = ? 
                              AND id != ? 
                              AND estado IN ('Activo', 'Vencido') 
                              ORDER BY id DESC LIMIT 1");
    $stmtPrev->execute([$loan['id_cliente'], $loan['id']]);
    $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    if ($prev) {
        $saldo = max(0, floatval($prev['monto_capital']) - floatval($prev['amortizado']));
        $neto = max(0, floatval($loan['monto_capital']) - $saldo);

        $sql = "UPDATE prestamos SET neto_entregar = ?, observaciones = CONCAT(IFNULL(observaciones,''), ' [Autocorrect: Neto ajustado por saldo prev L ', ? , ']') WHERE id = ?";
        $db->prepare($sql)->execute([$neto, number_format($saldo, 2), $loan['id']]);

        echo "AJUSTE: Prestamo #{$loan['id']} (Cliente {$loan['id_cliente']}) | Monto: {$loan['monto_capital']} | Saldo Prev (#{$prev['id']}): {$saldo} | Neto Nuevo: {$neto}\n";
    } else {
        echo "SKIP: Prestamo #{$loan['id']} - No se encontró préstamo previo activo.\n";
    }
}
echo "Proceso finalizado.\n";
?>