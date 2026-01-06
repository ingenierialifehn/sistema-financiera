<?php
echo "<h2>Diagnóstico de Conexión a Base de Datos</h2>";

// 1. Verificar Drivers
echo "<h3>1. Verificación de Drivers</h3>";
if (extension_loaded('pdo_pgsql')) {
    echo "<p style='color:green'>✅ Driver PDO MySQL (pdo_pgsql) está habilitado.</p>";
} else {
    echo "<p style='color:red'>❌ Driver PDO PostgreSQL (pdo_pgsql) NO está habilitado.</p>";
    echo "<p>Por favor habilite <code>extension=pdo_pgsql</code> en su php.ini</p>";
}

if (extension_loaded('pgsql')) {
    echo "<p style='color:green'>✅ Driver PostgreSQL (pgsql) está habilitado.</p>";
} else {
    echo "<p style='color:orange'>⚠️ Driver PostgreSQL estándar (pgsql) NO está habilitado (Recomendado pero no estrictamente necesario si usa PDO).</p>";
}

// 2. Intentar Conexión
echo "<h3>2. Prueba de Conexión</h3>";
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    echo "<p style='color:green'>✅ Conexión exitosa a Supabase!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Falló la conexión:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    
    // Intentar obtener más detalles si es PDOException
    if ($e instanceof PDOException) {
        echo "<p>Código de error PDO: " . $e->getCode() . "</p>";
    }
}

echo "<h3>3. Configuración Actual</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "</pre>";
