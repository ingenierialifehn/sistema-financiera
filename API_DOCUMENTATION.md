# Documentación API REST - Sistema Financiero

## 🔐 Autenticación

Todos los endpoints requieren autenticación mediante token Bearer:

```
Authorization: Bearer {token}
```

O mediante header personalizado:
```
X-Auth-Token: {token}
```

## 📋 Base URL

```
http://localhost/AplicacionesJFCC/sistema-financiera/app/api
```

---

## 👥 CLIENTES

### Listar Clientes
**GET** `/clientes/list.php`

**Query Parameters:**
- `page` (int, opcional): Número de página (default: 1)
- `limit` (int, opcional): Items por página (default: 20, max: 100)
- `search` (string, opcional): Búsqueda por nombre, código, documento o teléfono

**Permisos:** Admin, Cobrador (cobradores solo ven sus clientes asignados)

**Respuesta:**
```json
{
  "success": true,
  "message": "Clientes obtenidos exitosamente",
  "data": {
    "clientes": [...],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 50,
      "total_pages": 3
    }
  }
}
```

### Obtener Cliente
**GET** `/clientes/get.php?id={id}`

**Permisos:** Admin, Cobrador (solo sus clientes)

### Crear Cliente
**POST** `/clientes/create.php`

**Body:**
```json
{
  "nombre_completo": "Juan Pérez",
  "tipo_documento": "DNI",
  "numero_documento": "12345678",
  "telefono": "987654321",
  "email": "juan@example.com",
  "direccion": "Calle 123",
  "fecha_nacimiento": "1990-01-01",
  "ocupacion": "Comerciante",
  "cobrador_id": 2
}
```

**Permisos:** Solo Admin

**Campos requeridos:**
- `nombre_completo` (string, 3-100 caracteres)
- `tipo_documento` (DNI, RUC, CE)
- `numero_documento` (string, validado según tipo)
- `telefono` (string, 9-15 caracteres)

### Actualizar Cliente
**PUT** `/clientes/update.php`

**Body:**
```json
{
  "id": 1,
  "nombre_completo": "Juan Pérez Actualizado",
  "telefono": "987654322",
  "estado": "activo"
}
```

**Permisos:** Solo Admin

### Eliminar Cliente
**DELETE** `/clientes/delete.php?id={id}`

**Permisos:** Solo Admin

**Nota:** No se puede eliminar si tiene préstamos activos

---

## 💰 PRÉSTAMOS

### Listar Préstamos
**GET** `/prestamos/list.php`

**Query Parameters:**
- `page`, `limit`, `search` (igual que clientes)
- `estado` (string, opcional): pendiente, activo, completado, cancelado, en_mora
- `cliente_id` (int, opcional): Filtrar por cliente

**Permisos:** Admin, Cobrador (solo sus clientes), Cliente (solo sus préstamos)

### Obtener Préstamo
**GET** `/prestamos/get.php?id={id}`

Incluye información de cuotas.

**Permisos:** Admin, Cobrador, Cliente (con restricciones)

### Crear Préstamo
**POST** `/prestamos/create.php`

**Body:**
```json
{
  "cliente_id": 1,
  "monto_prestado": 5000.00,
  "tasa_interes": 5.0,
  "periodo_meses": 12,
  "fecha_desembolso": "2025-01-01",
  "dia_pago": 15,
  "observaciones": "Préstamo personal"
}
```

**Permisos:** Solo Admin

**Funcionalidades:**
- Calcula automáticamente `monto_total` y `monto_cuota`
- Genera todas las cuotas automáticamente
- Crea número de préstamo único

**Campos requeridos:**
- `cliente_id` (int)
- `monto_prestado` (decimal, min: 1)
- `tasa_interes` (decimal, 0-100%)
- `periodo_meses` (int, 1-120)
- `fecha_desembolso` (date)
- `dia_pago` (int, 1-28)

### Actualizar Préstamo
**PUT** `/prestamos/update.php`

Solo permite actualizar `observaciones` y `estado`.

**Body:**
```json
{
  "id": 1,
  "estado": "activo",
  "observaciones": "Observación actualizada"
}
```

**Permisos:** Solo Admin

**Nota:** No se puede actualizar si ya tiene pagos registrados

### Eliminar/Cancelar Préstamo
**DELETE** `/prestamos/delete.php?id={id}`

**Permisos:** Solo Admin

- Si tiene pagos: Solo cancela (estado = 'cancelado')
- Si no tiene pagos: Elimina completamente

### Obtener Cuotas de Préstamo
**GET** `/prestamos/cuotas.php?prestamo_id={id}`

**Permisos:** Admin, Cobrador, Cliente (con restricciones)

---

## 💵 PAGOS

### Listar Pagos
**GET** `/pagos/list.php`

**Query Parameters:**
- `page`, `limit`
- `prestamo_id` (int, opcional)
- `cliente_id` (int, opcional)
- `estado` (string, opcional): pendiente, confirmado, rechazado

**Permisos:** Admin, Cobrador (solo sus pagos), Cliente (solo sus pagos)

### Obtener Pago
**GET** `/pagos/get.php?id={id}`

**Permisos:** Admin, Cobrador, Cliente (con restricciones)

### Crear Pago
**POST** `/pagos/create.php`

**Body:**
```json
{
  "cuota_id": 1,
  "monto_pagado": 450.00,
  "fecha_pago": "2025-01-15",
  "metodo_pago": "efectivo",
  "comprobante_url": "https://cloudinary.com/image.jpg",
  "observaciones": "Pago en efectivo"
}
```

**Permisos:** Admin, Cobrador

**Funcionalidades:**
- Calcula automáticamente la mora si aplica
- Actualiza el estado de la cuota
- Actualiza el estado del préstamo si todas las cuotas están pagadas
- Registra quién hizo el cobro

**Campos requeridos:**
- `cuota_id` (int)
- `monto_pagado` (decimal, min: 0.01)
- `fecha_pago` (date)

**Campos opcionales:**
- `metodo_pago` (efectivo, transferencia, deposito, otro) - default: efectivo
- `comprobante_url` (string, URL de Cloudinary)
- `observaciones` (string, max: 500)

### Actualizar Pago
**PUT** `/pagos/update.php`

**Body:**
```json
{
  "id": 1,
  "estado": "confirmado",
  "comprobante_url": "https://cloudinary.com/nueva.jpg",
  "observaciones": "Observación actualizada"
}
```

**Permisos:** Solo Admin

**Nota:** Solo permite actualizar estado, comprobante y observaciones

### Eliminar Pago
**DELETE** `/pagos/delete.php?id={id}`

**Permisos:** Solo Admin

**Funcionalidades:**
- Revierte el pago en la cuota
- Actualiza el estado del préstamo si es necesario

---

## 📊 CÓDIGOS DE RESPUESTA HTTP

- `200` - OK (operación exitosa)
- `201` - Created (recurso creado)
- `400` - Bad Request (datos inválidos)
- `401` - Unauthorized (no autenticado)
- `403` - Forbidden (sin permisos)
- `404` - Not Found (recurso no encontrado)
- `405` - Method Not Allowed (método no permitido)
- `409` - Conflict (conflicto, ej: documento duplicado)
- `422` - Unprocessable Entity (error de validación)
- `500` - Internal Server Error (error del servidor)

---

## 🔒 SEGURIDAD

✅ **Prepared Statements**: Todas las consultas usan PDO prepared statements  
✅ **Validación de datos**: Validación y sanitización de todas las entradas  
✅ **Autorización por rol**: Cada endpoint verifica permisos según rol  
✅ **Logs de actividad**: Todas las acciones se registran en `logs_actividad`  
✅ **Prevención XSS**: Datos sanitizados antes de almacenar  
✅ **Prevención SQL Injection**: Uso exclusivo de prepared statements  

---

## 📝 NOTAS IMPORTANTES

1. **Formato de fechas**: YYYY-MM-DD (ej: 2025-01-15)
2. **Formato de moneda**: Decimales con 2 decimales (ej: 1234.56)
3. **Paginación**: Todos los listados soportan paginación
4. **Búsqueda**: Búsqueda parcial por texto en campos relevantes
5. **Roles**: Admin (acceso total), Cobrador (clientes asignados), Cliente (sus propios datos)

---

## 🧪 EJEMPLOS DE USO

### Crear Cliente y Préstamo

```javascript
// 1. Crear cliente
const cliente = await fetch('/app/api/clientes/create.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    nombre_completo: 'Juan Pérez',
    tipo_documento: 'DNI',
    numero_documento: '12345678',
    telefono: '987654321'
  })
});

// 2. Crear préstamo
const prestamo = await fetch('/app/api/prestamos/create.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    cliente_id: cliente.id,
    monto_prestado: 5000,
    tasa_interes: 5.0,
    periodo_meses: 12,
    fecha_desembolso: '2025-01-01',
    dia_pago: 15
  })
});

// 3. Registrar pago
const pago = await fetch('/app/api/pagos/create.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    cuota_id: prestamo.cuotas[0].id,
    monto_pagado: prestamo.monto_cuota,
    fecha_pago: '2025-02-15'
  })
});
```

---

## 📚 ENDPOINTS ADICIONALES

### Admin
- `GET /admin/summary.php` - Resumen de métricas
- `GET /admin/chart_payments.php?days=30` - Datos para gráfica
- `GET /admin/latest_payments.php?limit=20` - Últimos pagos

### Auth
- `POST /auth/login.php` - Iniciar sesión
- `POST /auth/logout.php` - Cerrar sesión

