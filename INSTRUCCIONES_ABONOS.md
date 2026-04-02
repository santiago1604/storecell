# Instrucciones para actualizar el sistema de abonos/anticipos

## Archivos modificados que debes copiar al hosting:

### 1. Vistas (resources/views/)
- `resources/views/pos/index.blade.php` - Ahora incluye botón y modal para registrar abonos

### 2. Controladores (app/Http/Controllers/)
- `app/Http/Controllers/CashSessionController.php` - Nuevo método addDeposit() y lógica actualizada en closeSummary()

### 3. Modelos (app/Models/)
- `app/Models/CashMovement.php` - Agregado campo 'payment_method' a $fillable

### 4. Rutas (routes/)
- `routes/web.php` - Nueva ruta POST /cash/deposit/add

### 5. Migraciones (database/migrations/)
- `database/migrations/2025_11_05_000001_add_payment_method_to_cash_movements.php`
- `database/migrations/manual_add_payment_method.sql` - **EJECUTAR ESTE SQL MANUALMENTE EN TU HOSTING**

---

## Pasos para actualizar en Unaux/Ezyro:

### 1. Base de datos
1. Accede a phpMyAdmin en tu hosting
2. Selecciona tu base de datos
3. Ve a la pestaña SQL
4. Copia y pega el contenido de `manual_add_payment_method.sql`
5. Ejecuta el SQL

### 2. Archivos PHP
1. Copia los siguientes archivos desde tu carpeta local a tu hosting (vía FTP/File Manager):
   - `htdocs/app/Http/Controllers/CashSessionController.php`
   - `htdocs/app/Models/CashMovement.php`
   - `htdocs/routes/web.php`
   - `htdocs/resources/views/pos/index.blade.php`

2. **IMPORTANTE**: También copia la nueva migración (aunque no la ejecutarás con artisan):
   - `htdocs/database/migrations/2025_11_05_000001_add_payment_method_to_cash_movements.php`

### 3. Limpiar cachés (opcional, si tienes acceso)
Si puedes ejecutar comandos artisan en tu hosting:
```bash
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Si no tienes acceso, simplemente borra manualmente:
- `htdocs/storage/framework/cache/*`
- `htdocs/storage/framework/views/*`

---

## Cómo funciona el nuevo sistema:

### Registrar un abono/anticipo:
1. En la pantalla POS, haz clic en el botón verde **"💰 Registrar abono/anticipo"**
2. Se abre un modal con 3 campos:
   - **Cliente/Descripción**: Nombre del cliente o motivo del abono
   - **Monto**: Cantidad del abono
   - **Método de pago**: Efectivo o Virtual
3. Al guardar, se registra automáticamente como movimiento tipo 'deposit'

### En el resumen de cierre:
- Los abonos ahora se identifican automáticamente por su tipo ('deposit')
- Ya no depende de buscar palabras como "abono" o "anticipo" en la descripción
- Se muestran separados por Efectivo/Virtual
- Se suman correctamente a los totales de "En caja" y "Virtual"

---

## Ventajas del sistema estandarizado:

✅ **Consistente**: Todos los abonos se registran igual, sin errores de escritura
✅ **Preciso**: Identifica abonos por tipo de movimiento, no por texto
✅ **Completo**: Incluye cliente, monto y método de pago
✅ **Reportes**: Fácil de filtrar y generar estadísticas
✅ **Simple**: Modal rápido desde POS, sin salir de la pantalla

---

## Compatibilidad con datos antiguos:

⚠️ **Nota importante**: Los movimientos antiguos que tenían "abono" o "anticipo" en la descripción NO tendrán el campo `payment_method` lleno. 

El sistema seguirá mostrándolos en el resumen, pero para los nuevos abonos se recomienda usar el botón del modal para que todo quede estandarizado.

---

## Verificación después de actualizar:

1. Abre la pantalla POS: https://storecell.unaux.com/pos
2. Verifica que aparezca el botón verde "💰 Registrar abono/anticipo"
3. Haz clic y prueba registrar un abono de prueba
4. Verifica que aparezca en "Últimos movimientos"
5. Al cerrar caja, confirma que el abono aparezca en la sección "Abonos/Anticipos"
