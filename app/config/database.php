<?php
/**
 * Configuración de Base de Datos
 * Ajustar según tu entorno de hosting
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // Configuración de base de datos (ajustar según InfinityFree o tu hosting)
            $host = 'localhost:3308';
            $dbname = 'sistema_financiera';
            $username = 'root'; // Ajustar según tu hosting
            $password = 'root'; // Ajustar según tu hosting

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);

        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            // Mensaje más descriptivo si falla
            throw new Exception("Error al conectar con la base de datos (Verifique drivers de PgSQL y credenciales)");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Prevenir clonación
    private function __clone()
    {
    }

    // Prevenir deserialización
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para obtener conexión
function getDB()
{
    return Database::getInstance()->getConnection();
}

