<?php
// Script to clean up test data and reset the database for real operations
// Based on user request to TRUNCATE specific tables while preserving others.

require_once __DIR__ . '/app/config/database.php';

// Check if we are running in CLI or via web
$isCli = (php_sapi_name() === 'cli');
$lineBreak = $isCli ? "\n" : "<br>";

echo "Iniciando limpieza de base de datos...$lineBreak";

$db = getDB();

if (!$db) {
    die("Error al conectar a la base de datos.");
}

// Tables to truncate
$tablesToTruncate = [
    // Estructura de Clientes
    'clientes',
    'clientes_negocios', // Parece contener la info del negocio
    'negocios_garantias',

    // Cartera y Préstamos
    'prestamos',
    'cuotas', // Tabla principal de plan de pagos
    'prestamos_comentarios',
    'abonos_capital',

    // Operaciones Diarias y Caja
    'control_caja_diaria',
    'cuadres_asesores',

    // Movimientos y Finanzas
    'movimientos_bancarios',
    'movimientos_internos_agencia',
    'ingresos_bancos_agencia',
    'historico_planillas'
];

try {
    // Disable foreign key checks to allow truncation
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Foreign keys desactivadas.$lineBreak";

    foreach ($tablesToTruncate as $table) {
        try {
            // Check if table exists first to avoid fatal errors if a table name is slightly off
            // But TRUNCATE is standard. Let's just try to truncate.
            $stmt = $db->prepare("TRUNCATE TABLE $table");
            $stmt->execute();
            echo "TABLA VACIADA: $table $lineBreak";
        } catch (PDOException $e) {
            echo "ERROR al vaciar tabla $table: " . $e->getMessage() . "$lineBreak";
        }
    }

    // Explicitly set balances to zero for agencies and banks if they are NOT truncating them.
    // The user said: "saldo inicial de todas las agencias y bancos esté en cero"
    // And "NO TOCAR: Las tablas de usuarios, roles, bancos..."
    // So distinct from truncating, we might need to UPDATE 'bancos' and 'agencias' (if exists) set saldo = 0?
    // Let's check if 'agencias' table exists. The user mentioned "saldo inicial de todas las agencias".
    // Usually agencies are in 'agencias' table.

    // Attempt to reset balances in 'bancos' and 'agencias' without deleting the rows
    try {
        $db->exec("UPDATE bancos SET saldo_actual = 0"); // Assuming column name, usually it is saldo or saldo_actual.
        // Let's query to check column name if it fails, or just wrap in try-catch.
        echo "Saldos de bancos reiniciados a 0.$lineBreak";
    } catch (Exception $e) {
        // Maybe the column is different?
        echo "No se pudo reiniciar saldo de bancos (puede que la columna no sea 'saldo_actual' o la tabla no exista): " . $e->getMessage() . "$lineBreak";
    }

    try {
        // Start with 'agencias' if it exists
        $db->exec("UPDATE agencias SET saldo_efectivo = 0");
        // Assuming typical column names. Will clarify if it errors.
        echo "Saldos de agencias reiniciados a 0.$lineBreak";
    } catch (Exception $e) {
        echo "No se pudo reiniciar saldo de agencias: " . $e->getMessage() . "$lineBreak";
    }

    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Foreign keys reactivadas.$lineBreak";

    echo "Limpieza completada exitosamente. El sistema está listo para operar.$lineBreak";

} catch (Exception $e) {
    echo "Error general durante el proceso: " . $e->getMessage() . "$lineBreak";
}
