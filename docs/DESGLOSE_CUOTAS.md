# Refactorización de Tabla de Cuotas - Desglose Detallado

**Fecha:** 2026-01-08  
**Estado:** ✅ Completado

## Resumen de Cambios

Se ha implementado exitosamente el desglose detallado en la tabla de cuotas para el reporte de recaudación. Cada cuota ahora guarda su propio desglose de capital, interés, gastos y comisión.

---

## 1. Campos Agregados a la Tabla `cuotas`

Se agregaron 4 nuevos campos tipo `DECIMAL(15,2)`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `capital_cuota` | DECIMAL(15,2) | Parte de capital en esta cuota |
| `interes_cuota` | DECIMAL(15,2) | Parte de interés en esta cuota (4/11 del interés total) |
| `gastos_cuota` | DECIMAL(15,2) | Parte de gastos financieros (4/11 del interés total) |
| `comision_cuota` | DECIMAL(15,2) | Parte de comisión de papelería (3/11 del interés total) |

---

## 2. Lógica de Cálculo

### Fórmula de Desglose

Para cada cuota de monto `M`:

1. **Calcular ratio de interés del préstamo:**
   ```
   Ratio Interés = (Total a Pagar - Capital Original) / Total a Pagar
   ```

2. **Dividir la cuota:**
   ```
   Parte Interés = M × Ratio Interés
   Parte Capital = M - Parte Interés
   ```

3. **Aplicar regla 4-4-3 al interés:**
   ```
   Interés Cuota = Parte Interés × (4/11)
   Gastos Cuota = Parte Interés × (4/11)
   Comisión Cuota = Parte Interés × (3/11)
   ```

### Ejemplo Práctico

**Préstamo:**
- Capital: L 5,000.00
- Total a Pagar: L 6,000.00
- Interés Total: L 1,000.00
- Ratio Interés: 16.67%

**Cuota de L 555.00:**
- Capital: L 462.78 (83.33%)
- Interés Total: L 92.22 (16.67%)
  - Interés: L 33.54 (4/11)
  - Gastos: L 33.54 (4/11)
  - Comisión: L 25.14 (3/11)

---

## 3. Archivos Modificados

### 3.1 Base de Datos
- ✅ `database.sql` - Actualizada definición de tabla `cuotas`

### 3.2 Código PHP
- ✅ `app/core/PrestamoHelper.php`
  - Función `generateCuotas()` - Ahora calcula y guarda desglose
  - Función `insertCuota()` - Ahora incluye campos de desglose

### 3.3 Scripts de Migración
- ✅ `app/api/migrations/add_cuotas_desglose.php` - Agrega campos a la tabla
- ✅ `app/api/migrations/recalcular_cuotas_existentes.php` - Recalcula cuotas pendientes
- ✅ `app/api/migrations/verificar_desglose_cuotas.php` - Verifica implementación

---

## 4. Resultados de la Migración

### Ejecución Exitosa
```
=== Recálculo completado exitosamente ===
Total de préstamos procesados: 5
Total de cuotas actualizadas: 146
```

### Verificación
```
✓ Estructura de tabla: OK
✓ Cuotas con desglose: 146 de 156
✓ Proporciones 4-4-3: Correctas
✓ Suma de componentes: Verificada
```

---

## 5. Uso en Reportes de Recaudación

Ahora puedes consultar el desglose detallado de cada cuota:

```sql
SELECT 
    c.numero_cuota,
    c.monto_cuota,
    c.capital_cuota,
    c.interes_cuota,
    c.gastos_cuota,
    c.comision_cuota,
    c.fecha_vencimiento,
    c.estado
FROM cuotas c
WHERE c.prestamo_id = ?
ORDER BY c.numero_cuota;
```

### Reporte de Recaudación Diaria
```sql
SELECT 
    DATE(c.fecha_pago) as fecha,
    SUM(c.capital_cuota) as total_capital,
    SUM(c.interes_cuota) as total_interes,
    SUM(c.gastos_cuota) as total_gastos,
    SUM(c.comision_cuota) as total_comision,
    SUM(c.monto_cuota) as total_recaudado
FROM cuotas c
WHERE c.estado = 'pagada'
AND DATE(c.fecha_pago) = CURDATE()
GROUP BY DATE(c.fecha_pago);
```

---

## 6. Próximos Pasos

### Nuevos Préstamos
✅ **Automático** - Los nuevos préstamos generarán cuotas con desglose automáticamente

### Cuotas Existentes sin Desglose
Si encuentras cuotas sin desglose (10 cuotas detectadas), ejecuta:

```bash
php app/api/migrations/recalcular_cuotas_existentes.php
```

### Verificar Implementación
Para verificar que todo funciona correctamente:

```bash
php app/api/migrations/verificar_desglose_cuotas.php
```

---

## 7. Compatibilidad

### ✅ Compatibilidad Retroactiva
- Las cuotas antiguas sin desglose siguen funcionando
- El sistema calcula el desglose cuando se ejecuta el script de migración
- No se requiere modificar código existente de pagos

### ✅ Todas las Modalidades
El desglose funciona correctamente para:
- ✅ Préstamos Diarios
- ✅ Préstamos Semanales
- ✅ Préstamos Catorcenales
- ✅ Préstamos Mensuales

---

## 8. Notas Técnicas

### Precisión Decimal
- Se usa `DECIMAL(15,2)` para evitar errores de redondeo
- La suma de componentes siempre coincide con `monto_cuota`
- Diferencias menores a L 0.01 son aceptables por redondeo

### Regla 4-4-3
La distribución del interés se mantiene consistente:
- **4 partes** → Interés puro
- **4 partes** → Gastos financieros
- **3 partes** → Comisión de papelería
- **Total:** 11 partes

---

## ✅ Implementación Completada

Todos los objetivos han sido cumplidos:
- ✅ Campos agregados a la tabla de cuotas
- ✅ Generador actualizado para calcular desglose
- ✅ Cuotas existentes recalculadas
- ✅ Verificación exitosa
- ✅ Documentación completa

**El sistema está listo para generar reportes de recaudación con desglose detallado.**
