# ✅ Módulo de Clientes - Listo para Móvil

## Estado Actual

### ✅ Ya Configurado:
1. **`clientes.php`** - BASE_URL dinámico en JavaScript ✅
2. **`clientes.js`** - Usa BASE_URL correctamente ✅
3. **Sidebar** - Enlaces relativos ✅
4. **Header** - Botón hamburguesa funcional ✅

## 📱 Cómo Usar en Móvil

### Paso 1: Acceder al Módulo
1. Abre el menú (botón ☰)
2. Toca "Clientes"
3. El menú se cerrará automáticamente

### Paso 2: Ver Lista de Clientes
- Deberías ver la tabla de clientes
- Puedes buscar usando el campo de búsqueda
- Puedes filtrar por estado y agencia

### Paso 3: Crear Nuevo Cliente
1. Toca el botón "Nuevo Cliente"
2. Se abrirá un modal con el formulario
3. Completa los datos en las pestañas:
   - **Datos Personales**: Nombre, DNI, teléfono, etc.
   - **Ubicación**: Dirección, GPS
   - **Documentación**: Fotos (DNI, perfil, casa, recibo)

### Paso 4: Obtener GPS (Opcional)
1. En la pestaña "Ubicación"
2. Toca "Obtener Ubicación"
3. Permite el acceso a la ubicación cuando el navegador lo pida
4. Las coordenadas se guardarán automáticamente

### Paso 5: Subir Fotos
1. En la pestaña "Documentación"
2. Toca en cada área para seleccionar una foto
3. O arrastra y suelta las fotos
4. Puedes tomar fotos directamente con la cámara del móvil

### Paso 6: Guardar
1. Toca "Guardar Cliente"
2. Espera la confirmación
3. Puedes ver la ficha del cliente o cerrar

## 🔍 Funcionalidades Disponibles

### En la Lista:
- ✅ Ver todos los clientes
- ✅ Buscar por nombre, DNI, código
- ✅ Filtrar por estado (activo/inactivo)
- ✅ Filtrar por agencia
- ✅ Ver ficha completa (👁️)
- ✅ Editar cliente (✏️)

### En el Formulario:
- ✅ Validación de DNI duplicado en tiempo real
- ✅ Captura de GPS/ubicación
- ✅ Subida de múltiples fotos
- ✅ Drag & drop de imágenes
- ✅ Vista previa de imágenes
- ✅ Navegación por pestañas

## 📸 Captura de Fotos en Móvil

Cuando toques para subir una foto:
1. El navegador te preguntará:
   - "Tomar foto" (abre la cámara)
   - "Elegir archivo" (abre la galería)
2. Selecciona la opción que prefieras
3. La foto se mostrará como vista previa
4. Puedes eliminarla y tomar otra si quieres

## 🗺️ Captura de GPS

Para obtener la ubicación:
1. Toca "Obtener Ubicación"
2. El navegador pedirá permiso
3. Toca "Permitir"
4. Las coordenadas se capturarán automáticamente

**Nota:** Si no funciona:
- Verifica que tengas GPS activo
- Verifica que tengas conexión a internet
- El campo es opcional, puedes guardar sin GPS

## ⚠️ Posibles Problemas y Soluciones

### Problema: "Error de conexión"
**Solución:**
- Verifica que estés en la misma red WiFi
- Recarga la página
- Revisa la consola del navegador (F12)

### Problema: No carga la lista de clientes
**Solución:**
- Abre la consola (F12)
- Busca errores en rojo
- Verifica que la URL de la API sea correcta:
  ```
  http://[TU-IP]/sistema-financiera/app/api/clientes/list.php
  ```

### Problema: No se suben las fotos
**Solución:**
- Verifica que las fotos no superen 5MB
- Usa formatos JPG o PNG
- Verifica permisos de la carpeta `uploads/documentos/`

### Problema: GPS no funciona
**Solución:**
- Permite el acceso a la ubicación en el navegador
- Activa el GPS del móvil
- Verifica que tengas conexión a internet
- Es opcional, puedes guardar sin GPS

## 🧪 Prueba Rápida

1. **Abre Clientes** desde el menú
2. **Toca "Nuevo Cliente"**
3. **Completa solo:**
   - Nombre completo
   - Número de documento
   - Teléfono
4. **Toca "Guardar Cliente"**
5. **Debería guardarse correctamente**

## 📊 APIs Utilizadas

El módulo usa estas APIs (todas con URLs dinámicas):
- `/app/api/clientes/list.php` - Listar clientes
- `/app/api/clientes/get.php` - Obtener un cliente
- `/app/api/clientes/create_with_files.php` - Crear cliente
- `/app/api/clientes/update.php` - Actualizar cliente
- `/app/api/clientes/check_dni.php` - Validar DNI
- `/app/api/agencias/list.php` - Listar agencias

## ✅ Checklist de Prueba

- [ ] Abre el módulo de clientes
- [ ] Se carga la lista de clientes
- [ ] Puedes buscar clientes
- [ ] Puedes abrir el formulario de nuevo cliente
- [ ] Puedes completar los datos personales
- [ ] Puedes obtener la ubicación GPS
- [ ] Puedes subir fotos
- [ ] Puedes guardar el cliente
- [ ] Recibes confirmación de guardado

## 🎯 Siguiente Paso

Prueba crear un cliente de prueba con datos mínimos:
- Nombre: "Cliente Prueba Móvil"
- DNI: "0801199900001"
- Teléfono: "99887766"

Luego me dices si funciona o qué error te aparece. 📱✨

---

**Fecha:** 2026-01-09  
**Estado:** ✅ Listo para Probar
