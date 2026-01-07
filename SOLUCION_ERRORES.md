# SOLUCIÓN DE ERRORES - Módulos Bóveda y Operaciones

## Problema
Los módulos están generando errores 500 porque faltan columnas y tablas en la base de datos.

## Solución

### Paso 1: Ejecutar el Script de Configuración

**Accede a esta URL en tu navegador:**
```
http://localhost/sistema-financiera/setup_completo_modulos.php
```

Este script creará/verificará:
- ✅ Columna `saldo_efectivo` en tabla `agencias`
- ✅ Tabla `ingresos_bancos_agencia`
- ✅ Estado 'aprobado' en tabla `prestamos`
- ✅ Columna `id_agencia` en tabla `prestamos`
- ✅ Columna `id_agencia` en tabla `clientes`

### Paso 2: Configurar Permisos

1. Ve a **Roles y Permisos** en el panel de administración
2. Edita el rol que necesita acceso (ej: "Oficial de Operaciones")
3. Marca los permisos para:
   - **Bóveda de Agencia** → Ver, Crear, Editar
   - **Operaciones** → Ver, Crear, Editar

### Paso 3: Asignar Agencia al Usuario

1. Ve a **Colaboradores**
2. Edita el usuario que usará estos módulos
3. Asegúrate de que tenga una **Agencia** asignada
4. Guarda los cambios

### Paso 4: Verificar

1. Cierra sesión y vuelve a iniciar sesión
2. Deberías ver los módulos "Bóveda de Agencia" y "Operaciones" en el menú
3. Al entrar, los datos deberían cargar correctamente

## Errores Comunes

### Error: "Usuario no tiene agencia asignada"
**Solución:** Asigna una agencia al usuario en su perfil de colaborador

### Error: "No tiene permisos"
**Solución:** Configura los permisos correctamente en Roles y Permisos

### Error: "Column 'saldo_efectivo' doesn't exist"
**Solución:** Ejecuta el script `setup_completo_modulos.php`

### Error: "Table 'ingresos_bancos_agencia' doesn't exist"
**Solución:** Ejecuta el script `setup_completo_modulos.php`

## Datos Opcionales

Si tienes clientes y préstamos existentes, puedes asignarles agencias manualmente:

```sql
-- Asignar agencia a clientes existentes
UPDATE clientes SET id_agencia = 1 WHERE id_agencia IS NULL;

-- Asignar agencia a préstamos existentes
UPDATE prestamos SET id_agencia = 1 WHERE id_agencia IS NULL;
```

(Reemplaza `1` con el ID de la agencia correspondiente)

## Soporte

Si los errores persisten después de seguir estos pasos:
1. Verifica los logs de error de PHP
2. Revisa la consola del navegador (F12)
3. Asegúrate de que todas las tablas existan en la base de datos
