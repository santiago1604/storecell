POS Tienda (Laravel Pack)
=========================

Este paquete contiene controladores, modelos, middleware, vistas y rutas
para montar rápidamente un POS en una instalación limpia de Laravel 11.

1) Crear proyecto Laravel:
   composer create-project laravel/laravel pos-tienda
   cd pos-tienda

2) Copiar el contenido de 'pos-pack' dentro del proyecto sobrescribiendo:
   - app/
   - resources/views/
   - routes/web.php
   - database/seeders/QuickSeed.php

3) Configurar .env con MySQL de XAMPP:
   DB_DATABASE=pos_tienda
   DB_USERNAME=root
   DB_PASSWORD=

4) Importar SQL (phpMyAdmin):
   /sql/pos_tienda.sql

5) Instalar dependencias e iniciar:
   composer install
   php artisan key:generate
   php artisan serve

Usuarios demo:
  admin@tienda.test / demo1234   (admin)
  vendedor@tienda.test / demo1234 (seller)
