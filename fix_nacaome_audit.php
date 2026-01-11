<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "--- Corrección Auditoría Nacaome via Script ---\n";

// 1. Identificar datos
$agenciaId = 3; // Nacaome
$loanId = 5;
$monto = 10000.00;
$obs = "Corrección Sistema: Devolución Automática por Préstamo Rechazado #5";

// 2. Insertar Movimiento Faltante
$sql = "INSERT INTO movimientos_internos_agencia 
        (id_agencia, id_usuario_operador, tipo_movimiento, monto, fecha_movimiento, observaciones)
        VALUES (?, ?, 'Ingreso por Rechazo', ?, NOW(), ?)";

$stmt = $db->prepare($sql);
$stmt->execute([$agenciaId, 1, $monto, $obs]); // 1 = Admin/System

echo "Movimiento insertado correctamente.\n";
