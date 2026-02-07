<?php
/**
 * Script de Importación Masiva de Clientes desde CSV
 * Uso: php importar_clientes_csv.php [nombre_archivo.csv]
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Helpers.php';

// Configuración
$archivoCSV = $argv[1] ?? 'plantilla_clientes.csv';
$delimitador = ',';

if (!file_exists($archivoCSV)) {
    die("Error: El archivo '$archivoCSV' no existe.\n");
}

$db = getDB();

echo "=== Importador de Clientes ===\n";
echo "Leyendo archivo: $archivoCSV\n";

$fila = 0;
$importados = 0;
$errores = 0;

if (($handle = fopen($archivoCSV, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
        $fila++;

        // Saltar encabezados
        if ($fila == 1 && (strtolower($data[0]) == 'nombre completo' || strtolower($data[0]) == 'nombre_completo')) {
            continue;
        }

        // Mapeo básico de columnas (ajustar según CSV real)
        // 0: Nombre, 1: TipoDoc, 2: NumDoc, 3: Tel, 4: Dir, 5: Email, 6: Ocupacion, 7: FechaNac
        $nombre = trim($data[0] ?? '');
        $tipoDoc = strtoupper(trim($data[1] ?? 'DNI'));
        $numDoc = trim($data[2] ?? '');
        $telefono = trim($data[3] ?? '');
        $direccion = trim($data[4] ?? '');
        $email = trim($data[5] ?? '');
        $ocupacion = trim($data[6] ?? '');
        $fechaNac = trim($data[7] ?? '');

        // Validaciones mínimas
        if (empty($nombre) || empty($numDoc)) {
            echo "Fila $fila: Saltada (Nombre o Documento vacío)\n";
            $errores++;
            continue;
        }

        // Verificar existencia
        $stmtCheck = $db->prepare("SELECT id FROM clientes WHERE numero_documento = ?");
        $stmtCheck->execute([$numDoc]);
        if ($stmtCheck->fetch()) {
            echo "Fila $fila: Saltada (Documento $numDoc ya existe)\n";
            $errores++;
            continue;
        }

        // Generar código
        $codigo = generateClienteCode();
        while ($db->query("SELECT id FROM clientes WHERE codigo_cliente = '$codigo'")->fetch()) {
            $codigo = generateClienteCode();
        }

        // Validar fecha
        if (empty($fechaNac) || $fechaNac == '0000-00-00') {
            $fechaNac = NULL;
        }

        try {
            $stmtInsert = $db->prepare("
                INSERT INTO clientes (
                    codigo_cliente, nombre_completo, tipo_documento, numero_documento,
                    telefono, direccion, email, ocupacion, fecha_nacimiento,
                    estado, created_at, id_agencia 
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    'Activo', NOW(), 1
                )
            ");
            // Nota: id_agencia fijado a 1 (Sede Central) para la importación inicial

            $stmtInsert->execute([
                $codigo,
                $nombre,
                $tipoDoc,
                $numDoc,
                $telefono,
                $direccion,
                $email,
                $ocupacion,
                $fechaNac
            ]);

            echo "Fila $fila: Importado - $nombre ($codigo)\n";
            $importados++;

        } catch (PDOException $e) {
            echo "Fila $fila: Error SQL - " . $e->getMessage() . "\n";
            $errores++;
        }
    }
    fclose($handle);
}

echo "=== Resumen ===\n";
echo "Procesados: $fila\n";
echo "Importados: $importados\n";
echo "Errores/Omitidos: $errores\n";
