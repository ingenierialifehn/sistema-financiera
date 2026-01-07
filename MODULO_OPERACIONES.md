# Módulo de Operaciones

## Descripción General
El módulo de **Operaciones** es el panel de control operativo de cada agencia, diseñado para que el Oficial de Operaciones gestione las actividades diarias de la sucursal.

## Características Principales

### 1. Título Dinámico
El título del módulo muestra: **"Operaciones [Nombre de la Agencia]"**
- Se obtiene automáticamente del `id_agencia` del usuario logueado
- Ejemplo: "Operaciones Sede Central"

### 2. Dashboard con 4 Tarjetas

#### Tarjeta 1: Bóveda
- **Color**: Índigo
- **Icono**: Vault (Bóveda)
- **Muestra**: Saldo actual de efectivo en la bóveda de la agencia
- **Fuente**: `agencias.saldo_efectivo`

#### Tarjeta 2: Clientes Totales
- **Color**: Verde
- **Icono**: Users (Usuarios)
- **Muestra**: Cantidad de clientes activos de la agencia
- **Fuente**: `clientes` WHERE `id_agencia` = usuario.agencia AND `estado` = 'activo'

#### Tarjeta 3: Créditos Aprobados
- **Color**: Amarillo
- **Icono**: Check Circle
- **Muestra**: Cantidad de préstamos en estado 'aprobado'
- **Fuente**: `prestamos` WHERE `id_agencia` = usuario.agencia AND `estado` = 'aprobado'

#### Tarjeta 4: Cartera en Calle
- **Color**: Púrpura
- **Icono**: Hand Holding USD
- **Muestra**: Monto total de préstamos activos pendientes de cobro
- **Cálculo**: SUM(monto_total) - SUM(monto_pagado) de préstamos activos

### 3. Sección "Bóveda Local"

**Panel de Información:**
- Saldo Actual de la bóveda
- Cantidad de desembolsos pendientes
- Monto total requerido para desembolsos

**Funcionalidad "Jalar Fondos desde Banco":**
- Botón para abrir modal de transferencia
- Reutiliza la lógica de transacciones del módulo de Bóveda
- Proceso:
  1. Seleccionar cuenta bancaria origen
  2. Ingresar monto
  3. Agregar referencia y observaciones
  4. Sistema ejecuta transacción:
     - Resta de `bancos.saldo_actual`
     - Suma a `agencias.saldo_efectivo`
     - Registra en `ingresos_bancos_agencia`

### 4. Tabla "Próximos Desembolsos"

**Muestra:**
- Préstamos en estado 'aprobado' de la agencia
- Filtrado por `id_agencia` del usuario

**Columnas:**
- N° Préstamo
- Cliente (nombre y teléfono)
- DNI
- Monto a desembolsar
- Fecha de aprobación
- Botón "Preparar Entrega"

**Botón "Preparar Entrega":**
- Marca el préstamo como listo para entrega
- Confirmación con SweetAlert2

## Instalación

### 1. Ejecutar script de configuración

```
http://localhost/sistema-financiera/setup_operaciones.php
```

Este script:
- Agrega el estado 'aprobado' al ENUM de `prestamos.estado`
- Crea columna `id_agencia` en tabla `prestamos`
- Crea columna `id_agencia` en tabla `clientes`

### 2. Configurar permisos

1. Ir a **Roles y Permisos**
2. Editar el rol deseado (ej: "Oficial de Operaciones")
3. Marcar permisos para módulo **"Operaciones"**:
   - **Ver**: Visualizar el módulo
   - **Crear**: Jalar fondos desde banco
   - **Editar**: Jalar fondos desde banco

### 3. Asignar agencia al usuario

El usuario debe tener `id_agencia` asignado en su perfil de colaborador.

## Filtrado por Agencia

**TODOS los datos mostrados están estrictamente filtrados por:**
```sql
WHERE id_agencia = [id_agencia del usuario en sesión]
```

Esto garantiza que:
- Cada agencia solo ve sus propios datos
- No hay cruce de información entre agencias
- Seguridad a nivel de datos

## Archivos Creados

### Backend (API)
- `/app/api/operaciones/get_dashboard.php` - Métricas del dashboard
- `/app/api/operaciones/get_proximos_desembolsos.php` - Lista de créditos aprobados

### Frontend
- `/public/admin/operaciones.php` - Vista principal
- `/public/admin/assets/js/operaciones.js` - Lógica JavaScript

### Base de Datos
- `setup_operaciones.php` - Script de configuración

## Seguridad

- ✅ Validación de permisos en cada endpoint
- ✅ Filtrado estricto por `id_agencia`
- ✅ Usuario debe tener agencia asignada
- ✅ Transacciones SQL atómicas
- ✅ Validación de saldo disponible

## Notas Técnicas

- Reutiliza el endpoint de Bóveda para jalar fondos
- Los estados de préstamo ahora incluyen: 'pendiente', 'aprobado', 'activo', 'completado', 'cancelado', 'en_mora'
- Formato de moneda: Lempiras (L.)
- Diseño responsive con Tailwind CSS
