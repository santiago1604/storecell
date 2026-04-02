<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Ajuste para hosting compartido: htdocs es la carpeta pública
$baseDir = dirname(__DIR__);

// Modo mantenimiento
if (file_exists($maintenance = $baseDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoload Composer
require $baseDir.'/vendor/autoload.php';

// Bootstrap Laravel
/** @var Application $app */
$app = require_once $baseDir.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
