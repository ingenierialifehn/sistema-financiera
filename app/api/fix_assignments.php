<?php
require_once __DIR__ . '/../../app/config/database.php';

// Asegurar que se renderice como HTML
header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['id_usuario'] ?? null;
$userName = $_SESSION['usuario'] ?? 'Usuario';

// Si no hay sesión, intentar recuperarla o pedir login
if (!$userId) {
    die("<html><body style='font-family:sans-serif; padding:40px; text-align:center;'>
            <h2 style='color:red;'>⚠️ Sesión no detectada</h2>
            <p>Por favor, inicia sesión en el sistema primero.</p>
            <a href='../../public/login.php' style='background:#2563eb; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Ir al Login</a>
         </body></html>");
}

$db = getDB();
$msg = "";

// Procesar Asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_all'])) {
        $stmt = $db->prepare("UPDATE prestamos SET asesor_creditos_id = ?, oficial_desembolsos_id = ? WHERE estado = 'Activo'");
        $stmt->execute([$userId, $userId]);
        $msg = "✅ SE HAN ASIGNADO TODOS LOS PRÉSTAMOS ACTIVOS A TU CARTERA.";
    } elseif (isset($_POST['assign_id'])) {
        $pid = intval($_POST['assign_id']);
        $stmt = $db->prepare("UPDATE prestamos SET asesor_creditos_id = ?, oficial_desembolsos_id = ? WHERE id = ?");
        $stmt->execute([$userId, $userId, $pid]);
        $msg = "✅ Préstamo #$pid asignado correctamente.";
    }
}

// Consultar Préstamos Activos
$sql = "SELECT p.id, p.monto_capital, c.nombre_completo, 
        u.username as asesor_actual, p.asesor_creditos_id
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        LEFT JOIN usuarios u ON p.asesor_creditos_id = u.id_usuario
        WHERE p.estado = 'Activo'
        ORDER BY p.id DESC";
$prestamos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Cartera</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            padding: 20px;
            background: #f3f4f6;
            color: #1f2937;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #111827;
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .btn-green {
            background: #10b981;
            color: white;
        }

        .btn-green:hover {
            background: #059669;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn-blue:hover {
            background: #1d4ed8;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .bg-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .bg-green {
            background: #d1fae5;
            color: #065f46;
        }

        .alert {
            background: #ecfdf5;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #a7f3d0;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header-flex">
            <div>
                <h1>🛠️ Administrador de Cartera</h1>
                <p style="margin:0;">Usuario Actual: <strong><?php echo htmlspecialchars($userName); ?> (ID:
                        <?php echo $userId; ?>)</strong></p>
            </div>
            <a href="../../public/admin/cobranza.php" class="btn btn-blue">← Volver a Cobranza</a>
        </div>

        <?php if ($msg): ?>
            <div class="alert">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div style="background:#fff7ed; padding:15px; border-radius:6px; border:1px solid #fed7aa; margin-bottom:20px;">
            <p style="margin:0; color:#9a3412;">
                <strong>Nota:</strong> Aquí puedes ver TODOS los préstamos activos del sistema.
                Si alguno no aparece en tu bandeja, es porque está asignado a otro usuario.
                Usa los botones para tomar control de ellos.
            </p>
        </div>

        <form method="POST" style="margin-bottom:20px;">
            <button type="submit" name="assign_all" class="btn btn-green"
                onclick="return confirm('¿Seguro que deseas tomar CONTROL TOTAL de toda la cartera activa? Esto moverá todos los clientes a tu nombre.')">
                ⚡ Asignarme TODA la Cartera Activa (<?php echo count($prestamos); ?>)
            </button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Asesor Actual</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $p): ?>
                    <?php $esMio = ($p['asesor_creditos_id'] == $userId); ?>
                    <tr style="<?php echo $esMio ? 'background:#f0fdf4' : ''; ?>">
                        <td>#<?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['nombre_completo']); ?></td>
                        <td>L <?php echo number_format($p['monto_capital'], 2); ?></td>
                        <td>
                            <?php echo $p['asesor_actual'] ? htmlspecialchars($p['asesor_actual']) : '<span style="color:red">Sin Asignar</span>'; ?>
                            <?php if ($p['asesor_creditos_id'] && !$p['asesor_actual'])
                                echo "(ID: {$p['asesor_creditos_id']})"; ?>
                        </td>
                        <td>
                            <?php if ($esMio): ?>
                                <span class="badge bg-green">En tu Cartera</span>
                            <?php else: ?>
                                <span class="badge bg-yellow">Ajeno</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$esMio): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="assign_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-blue"
                                        style="padding:4px 10px; font-size:12px;">Asignarme</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#059669; font-size:20px;">✓</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:#6b7280;">No hay préstamos activos en
                            el sistema.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>