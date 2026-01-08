# Sistema de Transacciones de Pagos

## 📋 Descripción General

El sistema ahora registra **CADA PAGO INDIVIDUAL** en una tabla separada (`transacciones_pagos`), además de mantener el estado actual en la tabla `cuotas`.

---

## 🎯 Objetivo

**Problema anterior:**
- Solo se veía el estado actual de la cuota
- No se sabía cuándo y cuánto pagó el cliente cada día
- Difícil hacer auditorías

**Solución:**
- Tabla `cuotas`: Estado ACTUAL de cada cuota
- Tabla `transacciones_pagos`: HISTORIAL completo de cada pago

---

## 📊 Estructura de Datos

### Tabla: `transacciones_pagos`

Cada fila = **UN PAGO INDIVIDUAL**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| `id` | ID único de la transacción | 1 |
| `prestamo_id` | ID del préstamo | 5 |
| `cuota_id` | ID de la cuota pagada | 42 |
| `monto_transaccion` | Monto de ESTE pago | L 250.00 |
| `tipo_pago` | Tipo: cuota, capital, mora | cuota |
| `capital_aplicado` | Capital pagado en ESTE pago | L 150.00 |
| `interes_aplicado` | Interés pagado en ESTE pago | L 60.00 |
| `gastos_aplicados` | Gastos pagados en ESTE pago | L 25.00 |
| `comision_aplicada` | Comisión pagada en ESTE pago | L 15.00 |
| `fecha_pago` | Fecha y hora del pago | 2026-01-08 10:30:00 |
| `usuario_cobro_id` | Quién cobró | 1 |
| `estado_cuota_despues` | Estado después del pago | parcial |
| `saldo_cuota_despues` | Saldo restante | L 250.00 |

---

## 💡 Ejemplo Práctico

### Escenario: Cliente paga una cuota en 3 días diferentes

**Cuota #5:**
- Monto total: L 500
- Capital: L 300
- Interés: L 120
- Gastos: L 50
- Comisión: L 30

---

### **Día 1 - 08/01/2026 10:00 AM**
Cliente paga: **L 200**

**Registro en `transacciones_pagos`:**
```sql
id: 1
prestamo_id: 5
cuota_id: 42
monto_transaccion: 200.00
capital_aplicado: 120.00  (40% de 300)
interes_aplicado: 48.00   (40% de 120)
gastos_aplicados: 20.00   (40% de 50)
comision_aplicada: 12.00  (40% de 30)
fecha_pago: 2026-01-08 10:00:00
estado_cuota_despues: parcial
saldo_cuota_despues: 300.00
```

**Estado en `cuotas`:**
```sql
cuota_id: 42
monto_pagado: 200.00
estado: parcial
```

---

### **Día 2 - 09/01/2026 14:30 PM**
Cliente paga: **L 150**

**Registro en `transacciones_pagos`:**
```sql
id: 2
prestamo_id: 5
cuota_id: 42
monto_transaccion: 150.00
capital_aplicado: 90.00   (30% de 300)
interes_aplicado: 36.00   (30% de 120)
gastos_aplicados: 15.00   (30% de 50)
comision_aplicada: 9.00   (30% de 30)
fecha_pago: 2026-01-09 14:30:00
estado_cuota_despues: parcial
saldo_cuota_despues: 150.00
```

**Estado en `cuotas`:**
```sql
cuota_id: 42
monto_pagado: 350.00  (200 + 150)
estado: parcial
```

---

### **Día 3 - 10/01/2026 09:15 AM**
Cliente paga: **L 150** (completa la cuota)

**Registro en `transacciones_pagos`:**
```sql
id: 3
prestamo_id: 5
cuota_id: 42
monto_transaccion: 150.00
capital_aplicado: 90.00   (30% de 300)
interes_aplicado: 36.00   (30% de 120)
gastos_aplicados: 15.00   (30% de 50)
comision_aplicada: 9.00   (30% de 30)
fecha_pago: 2026-01-10 09:15:00
estado_cuota_despues: pagada
saldo_cuota_despues: 0.00
```

**Estado en `cuotas`:**
```sql
cuota_id: 42
monto_pagado: 500.00  (200 + 150 + 150)
estado: pagada
```

---

## 📈 Reportes Posibles

### 1. **Historial de Pagos por Cliente**
```sql
SELECT 
    t.fecha_pago,
    c.numero_cuota,
    t.monto_transaccion,
    t.capital_aplicado,
    t.interes_aplicado,
    t.estado_cuota_despues,
    t.saldo_cuota_despues
FROM transacciones_pagos t
JOIN cuotas c ON t.cuota_id = c.id
WHERE t.prestamo_id = 5
ORDER BY t.fecha_pago DESC
```

### 2. **Pagos del Día**
```sql
SELECT 
    cl.nombre_completo,
    t.monto_transaccion,
    t.fecha_pago,
    c.numero_cuota
FROM transacciones_pagos t
JOIN prestamos p ON t.prestamo_id = p.id
JOIN clientes cl ON p.id_cliente = cl.id
JOIN cuotas c ON t.cuota_id = c.id
WHERE DATE(t.fecha_pago) = CURDATE()
ORDER BY t.fecha_pago DESC
```

### 3. **Total Recaudado por Concepto**
```sql
SELECT 
    DATE(fecha_pago) as fecha,
    SUM(capital_aplicado) as total_capital,
    SUM(interes_aplicado) as total_interes,
    SUM(gastos_aplicados) as total_gastos,
    SUM(comision_aplicada) as total_comision,
    SUM(monto_transaccion) as total_dia
FROM transacciones_pagos
WHERE MONTH(fecha_pago) = MONTH(CURDATE())
GROUP BY DATE(fecha_pago)
ORDER BY fecha DESC
```

---

## ✅ Ventajas del Nuevo Sistema

1. **Auditoría Completa**: Cada pago queda registrado con fecha y hora exacta
2. **Trazabilidad**: Sabes quién cobró cada pago
3. **Flexibilidad**: Puedes ver cuánto pagó el cliente cada día
4. **Reportes Detallados**: Puedes generar reportes por día, semana, mes
5. **Control**: Si un cliente paga de más, lo puedes ver claramente
6. **Histórico**: Nunca pierdes información de pagos anteriores

---

## 🔄 Flujo de Trabajo

```
CLIENTE PAGA L 250
       ↓
1. Actualiza tabla CUOTAS
   (estado actual)
       ↓
2. Inserta en TRANSACCIONES_PAGOS
   (registro histórico)
       ↓
3. Calcula desglose proporcional
       ↓
4. Guarda fecha, hora, usuario
       ↓
LISTO ✓
```

---

## 📝 Notas Importantes

- **NO se eliminan registros** de `transacciones_pagos`
- Cada pago es **inmutable** (no se modifica)
- La tabla `cuotas` sigue siendo la **fuente de verdad** del estado actual
- `transacciones_pagos` es solo para **historial y auditoría**

---

## 🎯 Próximos Pasos

1. Crear API para consultar transacciones
2. Agregar vista de historial de pagos en el detalle del préstamo
3. Generar reportes de recaudación diaria
4. Implementar exportación a Excel

---

**Fecha de Implementación:** 08/01/2026
**Versión:** 2.0
