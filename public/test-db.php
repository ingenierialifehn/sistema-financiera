<?php
/**
 * Test de conexión a base de datos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Conexión a Base de Datos</h2>";

try {
    require_once __DIR__ . '/../app/config/config.php';
    require_once __DIR__ . '/../app/config/database.php';
    
    echo "<p>✓ Archivos de configuración cargados</p>";
    
    $db = getDB();
    echo "<p>✓ Conexión a base de datos exitosa</p>";
    
    // Verificar si existe la tabla usuarios
    $stmt = $db->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ Tabla 'usuarios' existe</p>";
        
        // Verificar si hay usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "<p>Total de usuarios: " . $result['total'] . "</p>";
        
        // Verificar usuario admin
        $stmt = $db->prepare("SELECT id, usuario, email, rol, estado FROM usuarios WHERE usuario = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p>✓ Usuario 'admin' encontrado:</p>";
            echo "<pre>";
            print_r($admin);
            echo "</pre>";
            
            // Verificar contraseña
            $stmt = $db->prepare("SELECT password FROM usuarios WHERE usuario = 'admin'");
            $stmt->execute();
            $passData = $stmt->fetch();
            if ($passData) {
                echo "<p>Hash de contraseña: " . substr($passData['password'], 0, 20) . "...</p>";
                echo "<p>Test password_verify('admin123'): " . (password_verify('admin123', $passData['password']) ? '✓ OK' : '✗ FALLO') . "</p>";
            }
        } else {
            echo "<p>✗ Usuario 'admin' NO encontrado</p>";
        }
    } else {
        echo "<p>✗ Tabla 'usuarios' NO existe. Necesitas ejecutar database.sql</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

