<?php
require_once __DIR__ . '/../app/config/database.php';
header('Content-Type: text/plain');

$db = getDB();

echo "INICIANDO RESTAURACIÓN DE FÁBRICA V2...\n";

try {
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    } catch (Exception $e) {
    }

    // Vaciado 
    $tablas = [
        'abonos_capital',
        'agencias',
        'bancos',
        'cajas_agencias',
        'clientes',
        'clientes_negocios',
        'colaboradores',
        'configuraciones',
        'control_caja_diaria',
        'cuadres_asesores',
        'cuotas',
        'ingresos_bancos_agencia',
        'logs_actividad',
        'movimientos_bancarios',
        'movimientos_internos_agencia',
        'negocios_garantias',
        'prestamos',
        'prestamos_comentarios',
        'roles',
        'usuarios'
    ];
    foreach ($tablas as $tabla) {
        $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0)
            $db->exec("TRUNCATE TABLE $tabla");
    }
    echo "Tablas vaciadas.\n";

    // 3. Inserción de Datos CORREGIDA FINAL
    // Roles
    try {
        $db->exec("INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos, estado) VALUES
            (1, 'Administrador', 'Acceso total', '[]', 'Activo'),
            (2, 'Gerente', 'Gestión sucursal', '[]', 'Activo'),
            (3, 'Supervisor', 'Supervisión', '[]', 'Activo'),
            (4, 'Asesor', 'Gestión créditos', '[]', 'Activo'),
            (5, 'Cajero', 'Caja', '[]', 'Activo'),
            (6, 'Cliente', 'Consulta', '[]', 'Activo')");
        echo " + Roles OK.\n";
    } catch (Exception $e) {
        echo " ! Error Roles: " . $e->getMessage() . "\n";
    }

    // Agencia
    try {
        $db->exec("INSERT INTO agencias (id_agencia, nombre_agencia, direccion, ciudad, telefono_agencia, estado, saldo_efectivo) VALUES
            (1, 'Oficina Principal', 'Centro', 'Tegucigalpa', '2222-0000', 'Activa', 0.00)");
        echo " + Agencia OK.\n";
    } catch (Exception $e) {
        echo " ! Error Agencia: " . $e->getMessage() . "\n";
    }

    // Caja
    try {
        $db->exec("INSERT INTO cajas_agencias (id_agencia, saldo_caja_operativa, ultima_actualizacion) VALUES (1, 0.00, NOW())");
        echo " + Caja OK.\n";
    } catch (Exception $e) {
        echo " ! Error Caja: " . $e->getMessage() . "\n";
    }

    // Colaborador + User
    try {
        $db->exec("INSERT INTO colaboradores (id_colaborador, dni, nombre_completo, email, fecha_nacimiento, genero, puesto_cargo, id_agencia, fecha_ingreso, sueldo_base, estado_laboral) VALUES (1, '0000', 'Admin Sistema', 'admin@sys.com', '1990-01-01', 'Otro', 'Admin', 1, CURDATE(), 0, 'Activo')");

        $p = password_hash('password', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO usuarios (id_usuario, id_colaborador, id_rol, username, password_hash, rol, estado) VALUES (1, 1, 1, 'admin', '$p', 'admin', 'Activo')");
        echo " + Admin OK.\n";
    } catch (Exception $e) {
        echo " ! Error Usuario: " . $e->getMessage() . "\n";
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "FIN EXITOSO V2 \n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>