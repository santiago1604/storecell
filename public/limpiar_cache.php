    <?php
/**
 * LIMPIAR CACHE AGRESIVO - POS Tienda
 * Ejecuta este archivo accediendo a: https://storecell.unaux.com/limpiar_cache.php
 * Limpia TODO lo que pueda estar cacheado
 */

$base_path = dirname(__DIR__);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Limpiar Cache - POS Tienda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #f0fff4; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #fff5f5; border-left: 4px solid #dc3545; margin: 10px 0; }
        code { background: #f4f4f4; padding: 5px; border-radius: 3px; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🧹 Limpiando Cache (AGRESIVO)</h1>";

$dirs_to_clean = [
    'storage/framework/cache/data' => $base_path . '/storage/framework/cache/data',
    'storage/framework/views' => $base_path . '/storage/framework/views',
    'storage/framework/sessions' => $base_path . '/storage/framework/sessions',
    'bootstrap/cache' => $base_path . '/bootstrap/cache',
];

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDir($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

// Limpiar todos los directorios
foreach ($dirs_to_clean as $name => $path) {
    if (is_dir($path)) {
        deleteDir($path);
        @mkdir($path, 0755, true);
        echo "<div class='success'>✓ <code>$name</code> limpiada completamente</div>";
    }
}

// Limpiar todos los archivos de cache en bootstrap/cache
$cache_files = [
    'config.php',
    'services.php',
    'packages.php',
    'compiled.php',
];

foreach ($cache_files as $file) {
    $path = $base_path . '/bootstrap/cache/' . $file;
    if (file_exists($path)) {
        @unlink($path);
        echo "<div class='success'>✓ <code>bootstrap/cache/$file</code> eliminado</div>";
    }
}

echo "<hr>";
echo "<div class='success'><strong>✓ ¡LISTO!</strong> Cache limpiado completamente.</div>";
echo "<p>Recargando en 3 segundos...</p>";
echo "<script>
    setTimeout(() => {
        window.location.href = 'https://storecell.unaux.com/repairs';
    }, 3000);
</script>";

echo "</div></body></html>";
?>
