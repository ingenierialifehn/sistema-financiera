# Correcciones Realizadas en el Módulo de Reportes

## Fecha: 2026-01-08

## ⚠️ PROBLEMA CRÍTICO RESUELTO

### **Reporte de Recaudación Diaria NO mostraba todos los pagos**

**Problema:** El reporte solo mostraba 1 transacción cuando en realidad se habían realizado múltiples pagos en el día.

**Causa Raíz:** Las consultas SQL estaban buscando por `fecha_pago`, pero el sistema registra los pagos en la columna `fecha_pago_real`.

**Solución Implementada:**
- Se corrigieron **todas las consultas** en `recaudacion_diaria.php` para usar `fecha_pago_real`
- Ahora el reporte captura correctamente TODOS los pagos realizados en el día

**Cambios realizados:**
```sql
-- ANTES (INCORRECTO):
WHERE DATE(cu.fecha_pago) = ?

-- DESPUÉS (CORRECTO):
WHERE DATE(cu.fecha_pago_real) = ?
```

**Impacto:** El reporte ahora muestra la recaudación real del día, incluyendo todas las transacciones.

---

## Problemas Identificados y Corregidos

### 1. **Reporte de Recaudación Diaria** (`recaudacion_diaria.php`)

**Problema:** El cálculo del desglose de capital, interés, gastos y comisión no manejaba correctamente los casos donde las cuotas no tenían valores en las columnas de desglose.

**Solución Implementada:**
- Se agregó lógica condicional para verificar si la cuota tiene desglose (`capital_cuota > 0`)
- Si tiene desglose, se usan los valores de las columnas `capital_cuota`, `interes_cuota`, `gastos_cuota`, `comision_cuota`
- Si NO tiene desglose, se calcula automáticamente:
  - Capital = monto_pagado / 1.11
  - Interés = capital * 0.04
  - Gastos = capital * 0.04
  - Comisión = capital * 0.03

**Impacto:** Ahora el desglose de la recaudación diaria mostrará valores correctos incluso para cuotas antiguas que no tenían el desglose calculado.

---

### 2. **Reporte de Estado de Cartera** (`estado_cartera.php`)

**Problema 1: Capital en la Calle**
El cálculo estaba sumando el `total_a_pagar` (que incluye capital + intereses + gastos + comisión) menos lo pagado, lo cual inflaba incorrectamente el valor del capital en la calle.

**Solución:**
- Ahora se suma únicamente el `capital_cuota` de todas las cuotas pendientes (estado != 'pagada')
- Esto refleja el verdadero capital que está en la calle, sin incluir intereses

**Problema 2: Monto en Riesgo por Categoría**
El cálculo del saldo pendiente por préstamo estaba usando `total_a_pagar - total_pagado`, lo cual incluía intereses.

**Solución:**
- Se modificó la consulta para calcular el `capital_pendiente` sumando solo el `capital_cuota` de las cuotas pendientes
- Ahora el "monto en riesgo" refleja únicamente el capital pendiente, no el total con intereses

**Problema 3: Saldo Pendiente de Clientes en Mora**
Mismo problema que el anterior, el saldo pendiente incluía intereses.

**Solución:**
- Se actualizó la consulta para usar `capital_pendiente` en lugar de `total_a_pagar - total_pagado`
- Los clientes en mora ahora muestran solo el capital pendiente

**Impacto:** Los reportes de cartera ahora reflejan correctamente:
- El capital real en la calle (sin intereses)
- El monto en riesgo por categoría (solo capital)
- El saldo pendiente de clientes en mora (solo capital)

---

### 3. **Reporte de Desembolsos** (`desembolsos_periodo.php`)

**Problema:** Error SQL al intentar acceder a columnas `u.nombre` y `u.apellido` que no existen en la tabla `usuarios`.

**Error mostrado:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'u.nombre' in 'field list'
```

**Solución Implementada:**
- Se corrigió el JOIN para obtener el nombre del oficial de desembolsos
- Ahora se hace JOIN con la tabla `colaboradores` para obtener el `nombre_completo`
- Se usa `COALESCE(col.nombre_completo, u.username, 'N/A')` para manejar casos donde no hay colaborador asignado
- Se corrigió el campo de referencia de `u.id` a `u.id_usuario`

**Impacto:** El reporte de desembolsos ahora carga correctamente y muestra el nombre del oficial que realizó el desembolso.

---

## Archivos Modificados

1. `c:\xampp\htdocs\sistema-financiera\app\api\reportes\recaudacion_diaria.php`
2. `c:\xampp\htdocs\sistema-financiera\app\api\reportes\estado_cartera.php`
3. `c:\xampp\htdocs\sistema-financiera\app\api\reportes\desembolsos_periodo.php`

## Notas Importantes

### Diferencia entre Capital y Total a Pagar

- **Capital**: Es el monto principal del préstamo, el dinero que realmente se prestó al cliente
- **Total a Pagar**: Es el capital + intereses + gastos + comisión (11% adicional)

### Ejemplo:
Si un cliente tiene un préstamo de L 10,000:
- Capital: L 10,000
- Interés (4%): L 400
- Gastos (4%): L 400
- Comisión (3%): L 300
- **Total a Pagar: L 11,100**

**Antes de la corrección:**
- "Capital en la calle" mostraba L 11,100 (incorrecto)

**Después de la corrección:**
- "Capital en la calle" muestra L 10,000 (correcto)

## Recomendaciones

1. **Verificar los datos**: Revisar los reportes con datos reales para confirmar que los valores ahora son correctos
2. **Validar con contabilidad**: Comparar los valores del "capital en la calle" con los registros contables
3. **Monitorear**: Observar los reportes durante los próximos días para asegurar que funcionan correctamente

## Próximos Pasos

Si aún encuentras discrepancias en los datos, por favor especifica:
- ¿Qué reporte específico tiene datos incorrectos?
- ¿Qué valor está mal y cuál debería ser el valor correcto?
- ¿Hay algún cliente o préstamo específico que podamos usar como ejemplo?
