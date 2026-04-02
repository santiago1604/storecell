# 🎨 Sistema de Logo Personalizado

## ✅ Implementación Completa

Se ha agregado un sistema completo para que el administrador pueda personalizar el logo de la tienda.

## 🚀 Características

### 1. **Subida de Logo**
- El admin puede subir un logo desde `/settings`
- Formatos soportados: JPEG, PNG, JPG, GIF, SVG
- Tamaño máximo: 2MB
- Recomendación: Logo con fondo transparente (PNG) de aprox. 200x60px

### 2. **Visualización del Logo**
El logo aparece en:
- ✅ Barra de navegación superior (todas las páginas)
- ✅ Pantalla de login/registro
- ✅ Responsive en móviles y desktop

### 3. **Nombre de la Tienda**
- Si no hay logo, se muestra el nombre de la tienda
- El nombre también es personalizable desde `/settings`

### 4. **Gestión**
- Cambiar logo: Subir uno nuevo desde la configuración
- Eliminar logo: Marcar checkbox "Eliminar logo actual"
- El logo anterior se elimina automáticamente al subir uno nuevo

## 📍 Acceso

### Para administradores:
1. Ir al Dashboard
2. Hacer clic en "⚙️ Configuración" 
3. O acceder directamente a: `http://tu-dominio.com/settings`

### Ubicación en el menú:
- **Desktop**: Icono ⚙️ en la barra superior derecha (pantallas XL)
- **Móvil**: "⚙️ Configuración" en el menú hamburguesa
- **Dashboard**: Botón "⚙️ Configuración" al inicio

## 🗂️ Archivos Creados/Modificados

### Nuevos archivos:
- `database/migrations/2025_11_04_064524_create_settings_table.php` - Tabla para configuración
- `app/Models/Setting.php` - Modelo para gestionar configuración
- `app/Http/Controllers/SettingsController.php` - Controlador de configuración
- `resources/views/admin/settings.blade.php` - Vista de configuración

### Archivos modificados:
- `routes/web.php` - Agregadas rutas `/settings`
- `resources/views/layouts/app.blade.php` - Logo en navegación
- `resources/views/auth/login.blade.php` - Logo en login
- `resources/views/admin/dashboard.blade.php` - Enlace a configuración

## 💾 Base de Datos

### Tabla `settings`
```sql
- id (bigint)
- key (string, unique) - Ej: 'logo_path', 'store_name'
- value (text, nullable) - Valor de la configuración
- created_at (timestamp)
- updated_at (timestamp)
```

### Configuraciones disponibles:
- `logo_path`: Ruta del logo en storage (ej: `logos/mi-logo.png`)
- `store_name`: Nombre de la tienda (ej: "Mi Tienda POS")

## 📁 Almacenamiento

Los logos se guardan en:
- Ruta física: `storage/app/public/logos/`
- URL pública: `public/storage/logos/`

El enlace simbólico ya está creado con `php artisan storage:link`

## 🎯 Uso del Sistema

### Desde código Blade:
```php
@php
  $logo = \App\Models\Setting::get('logo_path');
  $storeName = \App\Models\Setting::get('store_name', 'POS Tienda');
@endphp

@if($logo)
  <img src="{{ asset('storage/' . $logo) }}" alt="{{ $storeName }}" />
@else
  <span>{{ $storeName }}</span>
@endif
```

### Desde PHP/Controlador:
```php
use App\Models\Setting;

// Obtener configuración
$logo = Setting::get('logo_path');
$storeName = Setting::get('store_name', 'POS Tienda');

// Establecer configuración
Setting::set('store_name', 'Nueva Tienda');
```

## 🔒 Seguridad

- Solo usuarios con rol `admin` pueden acceder a `/settings`
- Validación de tipos de archivo (solo imágenes)
- Validación de tamaño máximo (2MB)
- Los logos anteriores se eliminan automáticamente

## 📱 Responsive

El sistema es completamente responsive:
- Logo se ajusta automáticamente en móviles
- Altura máxima: 32px (móvil) / 40px (desktop)
- Ancho máximo: 150px (móvil) / 200px (desktop)

## 🎨 Recomendaciones de Diseño

Para mejores resultados:
1. **Formato**: PNG con fondo transparente
2. **Dimensiones**: 200x60 píxeles (horizontal)
3. **Peso**: Menor a 100KB para carga rápida
4. **Colores**: Contraste adecuado para visualización en blanco

## ✨ Próximos Pasos

Para probar el sistema:
1. Accede como admin al dashboard
2. Haz clic en "⚙️ Configuración"
3. Sube un logo de tu tienda
4. Guarda los cambios
5. Verifica que aparece en la navegación y en el login
