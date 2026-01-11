<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "Iniciando sincronización de movimientos bancarios faltantes (Jalar Fondos)...\n";

// 1. Obtener todos los ingresos a agencia (Jalar Fondos)
$stmt = $db->query("SELECT * FROM ingresos_bancos_agencia");
$ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($ingresos as $ing) {
    // 2. Buscar si existe en movimientos_bancarios
    // Criterio: Mismo banco, mismo monto, tipo egreso, fecha parecida (mismo día al menos)
    $stmtCheck = $db->prepare("
        SELECT id FROM movimientos_bancarios 
        WHERE banco_id = ? 
        AND tipo_transaccion = 'egreso' 
        AND monto = ? 
        AND DATE(fecha_hora) = DATE(?)
    ");
    $stmtCheck->execute([$ing['banco_id'], $ing['monto'], $ing['fecha_hora']]);
    $exists = $stmtCheck->fetchColumn();

    if (!$exists) {
        echo "Falta registro para Ingreso ID: {$ing['id']} - Monto: {$ing['monto']}\n";

        // 3. Insertar faltante
        $stmtInsert = $db->prepare("
            INSERT INTO movimientos_bancarios (
                banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, 
                descripcion, referencia, realizado_por, entidad_destino_tipo, entidad_destino_id, fecha_hora
            ) VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?, 'agencia', ?, ?)
        ");

        $desc = "Traslado de Fondos a Agencia (Corrección Auditoría)";

        $stmtInsert->execute([
            $ing['banco_id'],
            $ing['monto'],
            $ing['saldo_anterior_banco'], // Usamos el saldo snapshot que tenía el ingreso
            $ing['saldo_nuevo_banco'],
            $desc,
            $ing['referencia'],
            $ing['realizado_por'],
            $ing['agencia_id'],
            $ing['fecha_hora']
        ]);

        $count++;
    }
}

echo "Sincronización completada. Se crearon $count registros faltantes en movimientos_bancarios.\n";
