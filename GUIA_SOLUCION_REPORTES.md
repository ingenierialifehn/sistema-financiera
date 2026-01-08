# Guía de Solución de Problemas en Reportes

## Problema Resuelto: Error SQL en Reporte de Desembolsos

### ✅ Error Corregido
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'u.nombre' in 'field list'
```

**Causa:** El código intentaba acceder a columnas `u.nombre` y `u.apellido` que no existen en la tabla `usuarios`.

**Solución:** Se corrigió el JOIN para obtener el nombre del oficial desde la tabla `colaboradores`.

---

## Pasos para Verificar y Corregir los Reportes

### Paso 1: Verificar el Estado de los Datos

Abre en tu navegador:
```
http://localhost/sistema-financiera/app/api/reportes/verificar_desglose.php
```

Este script te mostrará:
- Cuántas cuotas tienen desglose calculado
- Cuántas cuotas NO tienen desglose
- Ejemplos de cuotas sin desglose

### Paso 2: Corregir Cuotas sin Desglose (Si es necesario)

Si el script anterior muestra que hay cuotas sin desglose, ejecuta:
```
http://localhost/sistema-financiera/app/api/reportes/corregir_desglose.php
```

Este script:
- Calculará automáticamente el desglose para todas las cuotas que no lo tienen
- Actualizará las columnas: `capital_cuota`, `interes_cuota`, `gastos_cuota`, `comision_cuota`

⚠️ **IMPORTANTE:** Este script modifica datos en la base de datos. Se recomienda hacer un backup antes de ejecutarlo.

### Paso 3: Verificar los Reportes

Ahora accede al módulo de reportes:
```
http://localhost/sistema-financiera/public/admin/reportes_agencia.php
```

Verifica que:
1. ✅ El reporte de **Recaudación Diaria** carga correctamente
2. ✅ El reporte de **Estado de Cartera** muestra datos correctos
3. ✅ El reporte de **Desembolsos** carga sin errores

---

## Problemas Corregidos

### 1. Recaudación Diaria
- ✅ Desglose de capital, interés, gastos y comisión ahora calcula correctamente
- ✅ Maneja cuotas con y sin desglose pre-calculado

### 2. Estado de Cartera
- ✅ "Capital en la calle" ahora muestra solo el capital pendiente (sin intereses)
- ✅ "Monto en riesgo" por categoría muestra solo capital
- ✅ Saldo pendiente de clientes en mora muestra solo capital

### 3. Desembolsos
- ✅ Error SQL corregido
- ✅ Nombre del oficial de desembolsos se muestra correctamente

---

## Si Aún Hay Problemas

### Verificar Errores en la Consola del Navegador

1. Abre las **Herramientas de Desarrollador** (F12)
2. Ve a la pestaña **Console**
3. Busca errores en rojo
4. Toma una captura de pantalla y compártela

### Verificar Errores en el Servidor

Revisa el archivo de logs de PHP:
```
C:\xampp\apache\logs\error.log
```

### Verificar la Estructura de la Base de Datos

Ejecuta en phpMyAdmin:
```sql
-- Verificar que existan las columnas de desglose
DESCRIBE cuotas;

-- Verificar datos de ejemplo
SELECT id, numero_cuota, monto_pagado, capital_cuota, interes_cuota, gastos_cuota, comision_cuota
FROM cuotas
WHERE estado = 'pagada'
LIMIT 5;
```

---

## Contacto y Soporte

Si después de seguir estos pasos aún hay problemas, proporciona:
1. Captura de pantalla del error
2. Mensaje de error completo de la consola
3. Resultado del script `verificar_desglose.php`
