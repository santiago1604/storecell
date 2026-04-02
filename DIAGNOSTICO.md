# Diagnóstico - Error 500 en /repairs

## Pasos para identificar el error en profreehost:

### 1. **Verificar el archivo de logs**
En profreehost, los logs generalmente están en:
- `storage/logs/laravel.log`

Accede vía FTP y abre este archivo para ver el error específico.

### 2. **Verificar archivo .env**
Asegúrate de que el archivo `.env` en el servidor tiene:
```
APP_DEBUG=true
APP_ENV=local
```

Esto te mostrará el error exacto en la pantalla.

### 3. **Ejecutar migraciones**
Es posible que las tablas no existan. Conecta vía SSH (si profreehost lo permite) y ejecuta:
```bash
php artisan migrate
```

Si no tienes acceso SSH, ejecuta esto vía web en una ruta accesible temporalmente.

### 4. **Problemas comunes en profreehost:**

**a) Extensión PDO_MYSQL no habilitada**
- Verifica en phpinfo() si tienes PDO_MYSQL

**b) Permisos de carpetas**
- Las carpetas `storage/` y `bootstrap/cache/` deben tener permisos 775

**c) Versión de PHP**
- Verifica que sea PHP 8.0+ (laravel 12 lo requiere)

**d) Composer autoload no actualizado**
- Si copiaste las carpetas, ejecuta: `composer dump-autoload -o`

### 5. **Solución rápida:**

Si tienes acceso al panel de profreehost:
1. Ve a "Administrador de archivos"
2. Busca `storage/logs/laravel.log`
3. Abre el archivo y copia el último error
4. Comparte ese error exacto para poder ayudarte mejor

### 6. **Validación de BD:**

Verifica que exista la tabla `repairs`:
```sql
SHOW TABLES LIKE 'repairs';
```

Si no existe, ejecuta las migraciones.

---

**Próximo paso:** Busca el error en `storage/logs/laravel.log` y compartelo conmigo.
