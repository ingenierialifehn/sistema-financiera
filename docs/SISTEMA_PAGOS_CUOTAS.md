# Sistema de Pagos Actualizado - Solo Tabla Cuotas

**Fecha:** 2026-01-08  
**Estado:** ✅ Completado

## Resumen de Cambios

El sistema de pagos ha sido refactorizado para trabajar **exclusivamente con la tabla `cuotas`**, eliminando la dependencia de una tabla `pagos` separada.

---

## 🔧 Cambios Implementados

### 1. **Eliminada Tabla `pagos`**
- ✅ La tabla `pagos` ha sido eliminada de la base de datos
- ✅ Todo el historial de pagos se gestiona desde la tabla `cuotas`

### 2. **Campos Utilizados en Tabla `cuotas`**

#### Campos de Control de Pago:
- `estado` - Estado de la cuota: `pendiente`, `parcial`, `pagada`
- `monto_cuota` - Monto total de la cuota
- `monto_pagado` - Monto acumulado pagado
- `fecha_vencimiento` - Cuándo vence la cuota
- **`fecha_pago_real`** - **Cuándo se realizó el pago** (DATETIME)
- **`usuario_cobro_id`** - Quién cobró el pago

#### Campos de Desglose:
- `capital_cuota` - Parte de capital
- `interes_cuota` - Parte de interés (4/11)
- `gastos_cuota` - Parte de gastos (4/11)
- `comision_cuota` - Parte de comisión (3/11)

---

## 📁 Archivos Modificados

### 1. **`app/api/cobranza/process_payment.php`**
**Cambios:**
- ✅ Usa `fecha_pago_real` para registrar cuándo se pagó
- ✅ Guarda `usuario_cobro_id` para saber quién cobró
- ✅ Actualiza estado de cuotas: `pendiente` → `pagada` o `parcial`
- ✅ Calcula total recaudado desde `cuotas` usando `fecha_pago_real`

**Ejemplo de actualización:**
```php
UPDATE cuotas 
SET estado = 'pagada', 
    fecha_pago_real = NOW(), 
    monto_pagado = monto_cuota,
    usuario_cobro_id = ?
WHERE id = ?
```

### 2. **`app/api/pagos/list.php`**
**Cambios:**
- ✅ Consulta directamente la tabla `cuotas`
- ✅ Filtra por `estado IN ('pagada', 'parcial')`
- ✅ Ordena por `fecha_pago_real DESC`
- ✅ Incluye desglose completo en la respuesta
- ✅ Calcula totales de capital, interés, gastos y comisión

**Filtros disponibles:**
- `fecha` - Filtra por fecha de pago
- `prestamo_id` - Filtra por préstamo
- `cliente_id` - Filtra por cliente
- `agencia_id` - Filtra por agencia

### 3. **`app/api/pagos/get.php`**
**Cambios:**
- ✅ Obtiene detalles de una cuota pagada
- ✅ Incluye información completa del cliente, préstamo y cobrador
- ✅ Muestra desglose detallado

---

## 🎯 Diferencia Entre Fechas

### `fecha_vencimiento` (DATE)
- **Propósito:** Cuándo **debe** pagarse la cuota
- **Se establece:** Al generar el calendario de pagos
- **Ejemplo:** `2026-01-15`

### `fecha_pago_real` (DATETIME)
- **Propósito:** Cuándo **se pagó** realmente la cuota
- **Se establece:** Al procesar el pago
- **Ejemplo:** `2026-01-08 09:30:00`

**Esto permite:**
- ✅ Saber si un pago fue a tiempo o atrasado
- ✅ Generar reportes de recaudación por fecha real de cobro
- ✅ Separar el calendario teórico del historial real

---

## 📊 Consultas Útiles

### Historial de Pagos del Día
```sql
SELECT 
    cu.numero_cuota,
    cu.monto_pagado,
    cu.fecha_pago_real,
    c.nombre_completo as cliente,
    u.nombre_completo as cobrador
FROM cuotas cu
INNER JOIN prestamos p ON cu.prestamo_id = p.id
INNER JOIN clientes c ON p.id_cliente = c.id
LEFT JOIN usuarios u ON cu.usuario_cobro_id = u.id_usuario
WHERE DATE(cu.fecha_pago_real) = CURDATE()
AND cu.estado IN ('pagada', 'parcial')
ORDER BY cu.fecha_pago_real DESC;
```

### Total Recaudado con Desglose
```sql
SELECT 
    DATE(cu.fecha_pago_real) as fecha,
    COUNT(*) as cuotas_pagadas,
    SUM(cu.monto_pagado) as total_recaudado,
    SUM(cu.capital_cuota) as total_capital,
    SUM(cu.interes_cuota) as total_interes,
    SUM(cu.gastos_cuota) as total_gastos,
    SUM(cu.comision_cuota) as total_comision
FROM cuotas cu
WHERE cu.estado IN ('pagada', 'parcial')
AND DATE(cu.fecha_pago_real) = CURDATE()
GROUP BY DATE(cu.fecha_pago_real);
```

### Pagos Atrasados
```sql
SELECT 
    cu.*,
    c.nombre_completo,
    DATEDIFF(cu.fecha_pago_real, cu.fecha_vencimiento) as dias_atraso
FROM cuotas cu
INNER JOIN prestamos p ON cu.prestamo_id = p.id
INNER JOIN clientes c ON p.id_cliente = c.id
WHERE cu.estado = 'pagada'
AND cu.fecha_pago_real > cu.fecha_vencimiento
ORDER BY dias_atraso DESC;
```

---

## 🔄 Flujo de Pago Actualizado

1. **Cliente realiza pago** → Se llama a `process_payment.php`
2. **Sistema busca cuotas pendientes** del préstamo
3. **Aplica el pago** en orden de vencimiento:
   - Actualiza `monto_pagado`
   - Establece `fecha_pago_real` = NOW()
   - Guarda `usuario_cobro_id`
   - Cambia `estado` a `pagada` o `parcial`
4. **Historial se consulta** desde `cuotas` filtrando por `fecha_pago_real`

---

## ✅ Ventajas del Nuevo Sistema

✅ **Simplicidad** - Una sola tabla para todo
✅ **Desglose automático** - Cada cuota tiene su desglose
✅ **Historial claro** - Se ve directamente en las cuotas
✅ **Fechas separadas** - Vencimiento vs pago real
✅ **Trazabilidad** - Se sabe quién cobró cada cuota
✅ **Reportes más fáciles** - Todo en una consulta

---

## 🧪 Pruebas Recomendadas

1. **Hacer un pago** y verificar que:
   - ✅ La cuota cambia a `pagada`
   - ✅ Se guarda `fecha_pago_real`
   - ✅ Se guarda `usuario_cobro_id`

2. **Ver historial de pagos** y verificar que:
   - ✅ Aparecen las cuotas pagadas
   - ✅ Muestra la fecha correcta
   - ✅ Muestra el monto correcto
   - ✅ Muestra el desglose

3. **Filtrar por fecha** y verificar que:
   - ✅ Solo muestra pagos de esa fecha
   - ✅ Los totales son correctos

---

**El sistema ahora está completamente funcional usando solo la tabla `cuotas`.** ✅
