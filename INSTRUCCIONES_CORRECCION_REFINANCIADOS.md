# 🔧 Corrección de Préstamos Refinanciados Antiguos

## Problema

Los préstamos que fueron refinanciados **antes** de implementar la lógica de cancelación automática quedaron con estado "Activo", causando:
- ❌ Aparecen en la lista de cobranza
- ❌ Se pueden hacer cobros duplicados
- ❌ El cliente aparece con múltiples préstamos activos

## Solución

He creado **2 scripts** para corregir este problema:

---

## Script 1: Corregir Préstamo Específico (#5)

### Para ejecutar:
```
http://localhost/sistema-financiera/app/api/migrations/marcar_prestamo_5_cancelado.php
```

### Qué hace:
- Verifica el estado actual del préstamo #5
- Busca si hay un refinanciamiento posterior
- Verifica el estado de las cuotas
- Actualiza el estado a "Cancelado" o "Finalizado" según corresponda

---

## Script 2: Corregir TODOS los Préstamos Refinanciados

### Para ejecutar:
```
http://localhost/sistema-financiera/app/api/migrations/corregir_prestamos_refinanciados.php
```

### Qué hace:
1. Busca todos los préstamos de tipo "Refinanciamiento"
2. Para cada uno, busca el préstamo original
3. Verifica si el préstamo original está "Activo"
4. Actualiza el estado según la lógica:
   - **Finalizado**: Si todas las cuotas están pagadas
   - **Cancelado**: Si aún hay cuotas pendientes

### Resultado esperado:
```json
{
  "success": true,
  "total_corregidos": 2,
  "detalles": [
    {
      "prestamo_original_id": 5,
      "refinanciamiento_id": 9,
      "estado_anterior": "Activo",
      "estado_nuevo": "Cancelado",
      "cuotas_pagadas": 15,
      "cuotas_total": 24
    }
  ]
}
```

---

## Pasos Recomendados

### 1. Ejecutar el script general
```
http://localhost/sistema-financiera/app/api/migrations/corregir_prestamos_refinanciados.php
```

### 2. Verificar los resultados
- Revisa el JSON de respuesta
- Verifica que los préstamos se actualizaron correctamente

### 3. Verificar en la interfaz
- Ve al módulo de cobranza
- Verifica que solo aparecen los préstamos activos
- Los préstamos cancelados NO deben aparecer

---

## Verificación Manual (SQL)

Si quieres verificar manualmente en la base de datos:

```sql
-- Ver todos los préstamos de un cliente
SELECT 
    id,
    tipo_prestamo,
    monto_capital,
    estado,
    fecha_solicitud,
    observaciones
FROM prestamos
WHERE id_cliente = [ID_CLIENTE]
ORDER BY id DESC;

-- Ver préstamos refinanciados que quedaron activos
SELECT 
    p1.id as original_id,
    p1.estado as original_estado,
    p2.id as refinanciamiento_id,
    p2.estado as refi_estado
FROM prestamos p1
JOIN prestamos p2 ON p1.id_cliente = p2.id_cliente 
    AND p2.id > p1.id 
    AND p2.tipo_prestamo = 'Refinanciamiento'
WHERE p1.estado = 'Activo'
    AND p1.tipo_prestamo != 'Refinanciamiento';
```

---

## Lógica de Estados

### Finalizado
- Todas las cuotas están pagadas
- El préstamo se completó exitosamente

### Cancelado
- El préstamo fue refinanciado
- Aún hay cuotas pendientes
- Se agrega observación: "[Cancelado por refinanciamiento - Nuevo préstamo #X]"

### Activo
- Solo los préstamos vigentes
- Son los únicos que aparecen en cobranza

---

## ⚠️ Importante

- ✅ Los scripts son seguros, solo actualizan el estado
- ✅ No modifican montos ni cuotas
- ✅ Agregan observaciones para trazabilidad
- ✅ Puedes ejecutarlos múltiples veces sin problema

---

## Después de la Corrección

Una vez ejecutados los scripts:

1. ✅ Los préstamos refinanciados estarán marcados como "Cancelado"
2. ✅ Solo los préstamos activos aparecerán en cobranza
3. ✅ No habrá riesgo de cobros duplicados
4. ✅ Los futuros refinanciamientos se marcarán automáticamente

---

**Fecha:** 2026-01-08 15:12
**Estado:** Scripts listos para ejecutar
