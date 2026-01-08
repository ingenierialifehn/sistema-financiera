<?php
// app/core/ClienteHelper.php

class ClienteHelper
{
    public static function calcularCategoriaRiesgo($db, $clienteId)
    {
        $sql = "SELECT c.fecha_vencimiento
                FROM cuotas c
                JOIN prestamos p ON c.prestamo_id = p.id
                WHERE p.id_cliente = ? 
                  AND p.estado = 'Activo'
                  AND c.estado != 'pagada'
                ORDER BY c.fecha_vencimiento ASC
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$clienteId]);
        $oldestDue = $stmt->fetchColumn();

        $diasMora = 0;
        if ($oldestDue) {
            $venc = new DateTime($oldestDue);
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            $venc->setTime(0, 0, 0);

            if ($venc < $hoy) {
                $diff = $hoy->diff($venc);
                $diasMora = $diff->days;
            }
        }

        $categoria = 'A';
        if ($diasMora == 0) {
            $categoria = 'A';
        } elseif ($diasMora <= 30) {
            $categoria = 'B';
        } elseif ($diasMora <= 60) {
            $categoria = 'C';
        } elseif ($diasMora <= 90) {
            $categoria = 'D';
        } else {
            $categoria = 'E';
        }

        return [
            'categoria' => $categoria,
            'dias_mora' => $diasMora
        ];
    }
}
?>