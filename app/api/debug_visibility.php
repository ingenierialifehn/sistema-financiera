<?php
require_once __DIR__ . '/../config/database.php';
session_start();
header('Content-Type: text/html');

echo "<style>body{font-family:sans-serif;padding:20px;line-height:1.5;} .fail{color:red;font-weight:bold;} .pass{color:green;} .info{background:#f3f4f6;padding:10px;border-radius:5px;}</style>";

$userId = $_SESSION['id_usuario'] ?? 0;
$userRole = $_SESSION['rol_nombre'] ?? '';
$userAgencia = $_SESSION['id_agencia'] ?? 'N/A';

echo "<h1>🕵️ Diagnóstico de Visibilidad</h1>";
echo "<div class='info'>";
echo "Usuario ID: <strong>$userId</strong><br>";
echo "Rol: <strong>$userRole</strong><br>";
echo "Agencia del Usuario: <strong>$userAgencia</strong><br>";
echo "</div><br>";

// Simular lógica de list.php
$isAsesor = (stripos($userRole, 'Asesor') !== false || stripos($userRole, 'Otorgamiento') !== false || stripos($userRole, 'Oficial') !== false);
echo "Es Asesor (Restringido): " . ($isAsesor ? "SÍ" : "NO") . "<br><br>";

$db = getDB();

// Traer TODAS las cuotas pendientes para analizar por qué se filtran
$sql = "SELECT c.id as cuota_id, c.numero_cuota, c.fecha_vencimiento, 
        p.id as prestamo_id, p.estado as estado_prestamo, 
        p.asesor_creditos_id, p.oficial_desembolsos_id,
        cl.nombre_completo, cl.id_agencia as agencia_cliente
        FROM cuotas c
        JOIN prestamos p ON c.prestamo_id = p.id
        JOIN clientes cl ON p.id_cliente = cl.id
        WHERE c.estado != 'pagada'
        AND p.estado = 'Activo'";

$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>Préstamo</th><th>Cliente</th><th>Agencia CL</th><th>Asesor P</th><th>Oficial P</th><th>Check Asesor</th><th>Check Agencia</th><th>Resultado</th></tr>";

foreach ($rows as $r) {
    echo "<tr>";
    echo "<td>#{$r['prestamo_id']} (C{$r['numero_cuota']})</td>";
    echo "<td>{$r['nombre_completo']}</td>";
    echo "<td>{$r['agencia_cliente']}</td>";
    echo "<td>{$r['asesor_creditos_id']}</td>";
    echo "<td>{$r['oficial_desembolsos_id']}</td>";

    // Chequeo ASESOR
    $passAsesor = true;
    if ($isAsesor) {
        if ($r['asesor_creditos_id'] == $userId || $r['oficial_desembolsos_id'] == $userId) {
            $passAsesor = true;
        } else {
            $passAsesor = false;
        }
    }

    // Chequeo AGENCIA
    $passAgencia = true;
    if ($isAsesor && !empty($userAgencia)) {
        // En cobranza.php, si es asesor, se fuerza el filtro de agencia
        if ($r['agencia_cliente'] == $userAgencia) {
            $passAgencia = true;
        } else {
            $passAgencia = false;
        }
    }

    echo "<td>" . ($passAsesor ? "<span class='pass'>PASA</span>" : "<span class='fail'>FALLA</span>") . "</td>";
    echo "<td>" . ($passAgencia ? "<span class='pass'>PASA</span>" : "<span class='fail'>FALLA (Dif Agencia)</span>") . "</td>";

    if ($passAsesor && $passAgencia) {
        echo "<td class='pass'>✅ VISIBLE</td>";
    } else {
        echo "<td class='fail'>❌ OCULTO</td>";
    }
    echo "</tr>";
}
echo "</table>";

if (empty($rows)) {
    echo "<p>No se encontraron cuotas pendientes de préstamos activos en absoluto.</p>";
}
?>