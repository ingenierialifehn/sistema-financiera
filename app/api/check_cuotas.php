<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/PrestamoHelper.php';
session_start();

$db = getDB();

// Si se solicita generar
if (isset($_POST['generar_prestamo_id'])) {
    $pid = intval($_POST['generar_prestamo_id']);

    // Obtener datos del prestamo
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmt->execute([$pid]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        // Eliminar por si acaso hay basura
        $db->prepare("DELETE FROM cuotas WHERE prestamo_id = ?")->execute([$pid]);

        // Generar
        PrestamoHelper::generateCuotasModalidad(
            $db,
            $pid,
            floatval($loan['valor_cuota']),
            intval($loan['plazo_meses']),
            date('Y-m-d'), // Fecha inicio HOY
            intval(date('d')),
            strtolower($loan['modalidad'])
        );
        $msg = "Plan de pagos generado correctamente para el préstamo #$pid.";
    }
}

// Listar Prestamos Activos
$sql = "SELECT p.id, c.nombre_completo, p.monto_capital, p.modalidad, p.valor_cuota 
        FROM prestamos p 
        JOIN clientes c ON p.id_cliente = c.id
        WHERE p.estado = 'Activo'";
$prestamos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Verificar Cuotas</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f0f2f5;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
        }

        .btn {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 10px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 4px;
            margin-top: 10px;
        }

        .success {
            padding: 10px;
            background: #dcfce7;
            color: #166534;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <h1>Estado de Cuotas (Préstamos Activos)</h1>

    <?php if (isset($msg))
        echo "<div class='success'>$msg</div>"; ?>

    <?php foreach ($prestamos as $p): ?>
        <?php
        // Contar cuotas
        $stmtC = $db->query("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = " . $p['id']);
        $numCuotas = $stmtC->fetchColumn();
        ?>
        <div class="card">
            <h3>Préstamo #
                <?php echo $p['id']; ?> -
                <?php echo $p['nombre_completo']; ?>
            </h3>
            <p>
                Monto: L
                <?php echo number_format($p['monto_capital'], 2); ?> |
                Modalidad:
                <?php echo $p['modalidad']; ?>
            </p>
            <p><strong>Cuotas Existentes:
                    <?php echo $numCuotas; ?>
                </strong></p>

            <?php if ($numCuotas == 0): ?>
                <div class="alert">
                    ⚠️ ERROR CRÍTICO: Este préstamo está ACTIVO pero NO TIENE PLAN DE PAGOS. <br>
                    Por eso no aparece en cobranza.
                </div>
                <form method="POST" style="margin-top:15px;">
                    <input type="hidden" name="generar_prestamo_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="btn">🛠️ Generar Plan de Pagos Ahora</button>
                </form>
            <?php else: ?>
                <div style="color:green;">✓ Plan de pagos correcto.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <br>
    <a href="../../public/admin/cobranza.php" class="btn" style="background:#4b5563;">Volver a Cobranza</a>

</body>

</html>