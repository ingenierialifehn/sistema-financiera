<?php
echo "DIAGNOSTICO DE BASE DE DATOS\n";
echo "============================\n";

// 1. Verificar Drivers
echo "1. VERIFICACION DE DRIVERS:\n";
if (extension_loaded('pdo_pgsql')) {
    echo "  [OK] Driver PDO MySQL (pdo_pgsql) esta habilitado.\n";
} else {
    echo "  [ERROR] Driver PDO PostgreSQL (pdo_pgsql) NO esta habilitado.\n";
}

if (extension_loaded('pgsql')) {
    echo "  [OK] Driver PostgreSQL (pgsql) esta habilitado.\n";
} else {
    echo "  [WARN] Driver PostgreSQL estandar (pgsql) NO esta habilitado.\n";
}

// 2. Intentar Conexión
echo "\n2. PRUEBA DE CONEXION:\n";
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    echo "  [OK] Conexion exitosa a Supabase!\n";
} catch (Exception $e) {
    echo "  [ERROR] Fallo la conexion:\n";
    echo "  " . $e->getMessage() . "\n";
}
