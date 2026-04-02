<?php
/**
 * VER LOG DE ERRORES - POS Tienda
 * Ejecuta este archivo accediendo a: https://storecell.unaux.com/ver_log.php
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Ver Log - POS Tienda</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4ec9b0; border-bottom: 2px solid #4ec9b0; padding-bottom: 10px; }
        pre { background: #252526; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 600px; border-left: 4px solid #007acc; }
        .error { color: #f48771; font-weight: bold; }
        .warning { color: #dcdcaa; }
        .success { color: #6a9955; }
    </style>
</head>
<body>
<div class='container'>
    <h1>📋 Archivo de Log de Laravel</h1>";

$base_path = dirname(__DIR__);
$log_file = $base_path . '/storage/logs/laravel.log';

if (!file_exists($log_file)) {
    echo "<p style='color: #d16969;'>❌ El archivo de log no existe aún</p>";
    echo "</div></body></html>";
    exit;
}

$file_size = filesize($log_file);
echo "<p>Tamaño del archivo: <strong>" . number_format($file_size) . " bytes</strong></p>";
echo "<p>Última modificación: <strong>" . date('Y-m-d H:i:s', filemtime($log_file)) . "</strong></p>";

echo "<h2>Últimas 100 líneas del log:</h2>";
echo "<pre>";

$lines = file($log_file);
$last_lines = array_slice($lines, -100);

foreach ($last_lines as $line) {
    $display_line = htmlspecialchars($line);
    
    // Colorear según contenido
    if (strpos($line, 'Exception') !== false || strpos($line, 'Error') !== false) {
        $display_line = "<span class='error'>" . $display_line . "</span>";
    } elseif (strpos($line, 'warning') !== false) {
        $display_line = "<span class='warning'>" . $display_line . "</span>";
    } elseif (strpos($line, 'successfully') !== false || strpos($line, 'created') !== false) {
        $display_line = "<span class='success'>" . $display_line . "</span>";
    }
    
    echo $display_line;
}

echo "</pre>";
echo "</div></body></html>";
?>
