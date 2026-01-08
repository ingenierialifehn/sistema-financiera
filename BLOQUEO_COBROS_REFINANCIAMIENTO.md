# 🔒 Bloqueo de Cobros Durante Refinanciamiento

## 📋 Funcionalidad Implementada

Cuando un cliente solicita un refinanciamiento o reestructuración, el **botón de cobrar** en su préstamo activo se bloquea automáticamente hasta que la solicitud sea aprobada o rechazada.

---

## 🔄 Flujo del Proceso

### 1. Cliente con Préstamo Activo
```
Estado: Activo
Botón: ✅ "Cobrar" (Habilitado)
```

### 2. Se Solicita Refinanciamiento
El asesor presiona "Solicitar Refinanciamiento" desde el modal de cobro:
```
Acción: Se crea un nuevo préstamo
  - Estado: "Solicitado"
  - Tipo: "Refinanciamiento"
  - Observaciones: "SOLICITUD DE REFINANCIAMIENTO del Préstamo #X"
```

### 3. Préstamo Activo se Bloquea
```
Préstamo Original:
  - Estado: Activo (sin cambios)
  - Botón: 🔒 "En Proceso" (Bloqueado)
  - Mensaje: "Este cliente tiene una solicitud de refinanciamiento en proceso"
```

### 4. Proceso de Aprobación
El nuevo préstamo pasa por los estados:
```
Solicitado → En Análisis → Verificación de Campo → Aprobado → Listo para Entrega
```

Durante **TODOS** estos estados, el préstamo activo permanece bloqueado.

### 5. Desembolso del Refinanciamiento
Cuando se desembolsa el nuevo préstamo:
```
Préstamo Original:
  - Estado: "Refinanciado" ✅
  - Botón: No aparece en cobranza

Préstamo Nuevo:
  - Estado: "Activo" ✅
  - Botón: ✅ "Cobrar" (Habilitado)
```

---

## 🔍 Lógica de Detección

El sistema detecta si un cliente tiene una solicitud pendiente verificando:

### Condiciones:
1. **Mismo cliente** (`id_cliente` coincide)
2. **Préstamo diferente** (`p2.id != p.id`)
3. **Tipo o Observaciones** indican refinanciamiento:
   - `tipo_prestamo = 'Refinanciamiento'`
   - O `observaciones LIKE '%SOLICITUD DE REFINANCIAMIENTO%'`
   - O `observaciones LIKE '%SOLICITUD DE REESTRUCTURACIÓN%'`
4. **Estado en proceso**:
   - Solicitado
   - En Análisis
   - Verificación de Campo
   - Pendiente de Operaciones
   - Aprobado
   - Listo para Entrega

### SQL Query:
```sql
SELECT COUNT(*) 
FROM prestamos p2 
WHERE p2.id_cliente = p.id_cliente 
AND p2.id != p.id
AND (
   p2.tipo_prestamo = 'Refinanciamiento' 
   OR p2.observaciones LIKE '%SOLICITUD DE REFINANCIAMIENTO%'
   OR p2.observaciones LIKE '%SOLICITUD DE REESTRUCTURACIÓN%'
)
AND p2.estado IN (
   'Solicitado', 
   'En Análisis', 
   'Verificación de Campo', 
   'Pendiente de Operaciones', 
   'Aprobado', 
   'Listo para Entrega'
) > 0
```

---

## 🎨 Interfaz de Usuario

### Botón Bloqueado
Cuando hay una solicitud pendiente:
```html
<button class="bg-gray-300 text-gray-600 cursor-not-allowed">
  <i class="fas fa-lock"></i> En Proceso
</button>
```

### Mensaje al Usuario
Al hacer clic en el botón bloqueado:
```
⚠️ Atención
Este cliente tiene una solicitud de refinanciamiento en proceso. 
No se pueden realizar cobros hasta que se apruebe o rechace la solicitud.
```

---

## ✅ Estados que Bloquean Cobros

| Estado | Bloquea Cobros | Razón |
|--------|----------------|-------|
| Solicitado | ✅ Sí | Solicitud creada |
| En Análisis | ✅ Sí | En revisión |
| Verificación de Campo | ✅ Sí | Verificando datos |
| Pendiente de Operaciones | ✅ Sí | Esperando aprobación |
| Aprobado | ✅ Sí | Aprobado, pendiente desembolso |
| Listo para Entrega | ✅ Sí | Listo para desembolsar |

---

## ❌ Estados que NO Bloquean Cobros

| Estado | Bloquea Cobros | Razón |
|--------|----------------|-------|
| Activo | ❌ No | Préstamo ya desembolsado |
| Finalizado | ❌ No | Préstamo completado |
| Rechazado | ❌ No | Solicitud rechazada |
| Refinanciado | ❌ No | Ya fue refinanciado |

---

## 🔧 Archivo Modificado

**Archivo:** `app/api/cobranza/list_grouped.php`

**Líneas:** 47-60

**Cambios:**
- Agregado `p2.id != p.id` para excluir el mismo préstamo
- Agregado verificación por observaciones (para solicitudes manuales)
- Cambiado a lista explícita de estados en proceso

---

## 📝 Casos de Uso

### Caso 1: Solicitud de Refinanciamiento
```
1. Cliente tiene Préstamo #5 (Activo)
2. Asesor solicita refinanciamiento
3. Se crea Préstamo #10 (Solicitado, Tipo: Refinanciamiento)
4. Préstamo #5 → Botón bloqueado ✅
5. Se aprueba y desembolsa Préstamo #10
6. Préstamo #5 → Estado: Refinanciado
7. Préstamo #10 → Estado: Activo, Botón habilitado ✅
```

### Caso 2: Solicitud Rechazada
```
1. Cliente tiene Préstamo #5 (Activo)
2. Asesor solicita refinanciamiento
3. Se crea Préstamo #10 (Solicitado)
4. Préstamo #5 → Botón bloqueado ✅
5. Se rechaza Préstamo #10
6. Préstamo #10 → Estado: Rechazado
7. Préstamo #5 → Botón habilitado nuevamente ✅
```

---

## ⚠️ Importante

### Prevención de Cobros Duplicados
Esta funcionalidad previene:
- ❌ Cobrar al préstamo viejo mientras se procesa el refinanciamiento
- ❌ Confusión sobre cuál préstamo cobrar
- ❌ Doble cobro al cliente

### Transparencia
El cliente sabe que:
- ✅ Su solicitud está en proceso
- ✅ No puede hacer pagos al préstamo viejo
- ✅ Debe esperar la aprobación del refinanciamiento

---

## 🧪 Verificación

### Para probar:
1. Abre el módulo de cobranza
2. Selecciona un cliente con préstamo activo
3. Presiona "Solicitar Refinanciamiento"
4. Completa el formulario y envía
5. Verifica que el botón "Cobrar" cambie a "En Proceso" 🔒
6. Intenta hacer clic → Debe mostrar el mensaje de advertencia

---

**Fecha de implementación:** 2026-01-08 15:23
**Estado:** ✅ IMPLEMENTADO
