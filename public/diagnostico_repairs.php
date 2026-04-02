<?php
/**
 * DIAGNÓSTICO ESPECÍFICO - Error 500 en /repairs
 * Ejecuta este archivo accediendo a: https://storecell.unaux.com/diagnostico_repairs.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico Repairs - POS Tienda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #007bff; padding-left: 10px; }
        .check { margin: 15px 0; padding: 10px; border-left: 4px solid #28a745; background: #f0fff4; }
        .error { margin: 15px 0; padding: 10px; border-left: 4px solid #dc3545; background: #fff5f5; }
        .warning { margin: 15px 0; padding: 10px; border-left: 4px solid #ffc107; background: #fffbf0; }
        .info { margin: 15px 0; padding: 10px; border-left: 4px solid #17a2b8; background: #f0f7ff; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Diagnóstico Específico - Error en /repairs</h1>";

$base_path = dirname(__DIR__);

// 1. Intentar cargar Laravel
echo "<h2>Cargando Laravel...</h2>";
try {
    // Cargar el autoloader
    require_once $base_path . '/vendor/autoload.php';
    echo "<div class='check'><span class='status-ok'>✓</span> Autoloader cargado</div>";
    
    // Cargar variables de entorno
    if (file_exists($base_path . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable($base_path);
        $dotenv->load();
        echo "<div class='check'><span class='status-ok'>✓</span> Variables de entorno cargadas</div>";
    }
    
    // Crear la aplicación
    $app = require_once $base_path . '/bootstrap/app.php';
    echo "<div class='check'><span class='status-ok'>✓</span> Aplicación Laravel inicializada</div>";
    
    // Obtener el kernel
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    echo "<div class='check'><span class='status-ok'>✓</span> Kernel HTTP cargado</div>";
    
    // Inicializar BD
    $db = $app->make('db');
    echo "<div class='check'><span class='status-ok'>✓</span> Conexión de BD inicializada</div>";
    
    // 2. Verificar modelos
    echo "<h2>Verificando Modelos...</h2>";
    
    try {
        $user = \App\Models\User::first();
        echo "<div class='check'><span class='status-ok'>✓</span> Modelo User: OK</div>";
    } catch (Exception $e) {
        echo "<div class='error'><span class='status-error'>✗</span> Modelo User: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    try {
        $repair = \App\Models\Repair::first();
        echo "<div class='check'><span class='status-ok'>✓</span> Modelo Repair: OK</div>";
    } catch (Exception $e) {
        echo "<div class='error'><span class='status-error'>✗</span> Modelo Repair: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 3. Verificar relaciones en Repair
    echo "<h2>Verificando Relaciones del Modelo Repair...</h2>";
    
    try {
        $repair = \App\Models\Repair::with('receivedBy', 'technician')->first();
        if ($repair) {
            echo "<div class='check'><span class='status-ok'>✓</span> Repair con relaciones cargado exitosamente</div>";
            echo "<pre>";
            echo "Repair ID: " . $repair->id . "\n";
            echo "Customer: " . $repair->customer_name . "\n";
            if ($repair->receivedBy) {
                echo "Received By: " . $repair->receivedBy->name . "\n";
            }
            if ($repair->technician) {
                echo "Technician: " . $repair->technician->name . "\n";
            }
            echo "</pre>";
        } else {
            echo "<div class='warning'>ℹ No hay reparaciones en la BD aún (normal si es nueva)</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'><span class='status-error'>✗</span> Error cargando relaciones: <br>";
        echo htmlspecialchars($e->getMessage()) . "<br>";
        echo htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</div>";
    }
    
    // 4. Simular la consulta del controlador
    echo "<h2>Simulando Consulta del Controlador...</h2>";
    
    try {
        $repairs = \App\Models\Repair::query()
            ->with(['receivedBy', 'technician'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        
        echo "<div class='check'><span class='status-ok'>✓</span> Consulta ejecutada exitosamente</div>";
        echo "<p>Total de reparaciones encontradas: " . count($repairs) . "</p>";
    } catch (Exception $e) {
        echo "<div class='error'><span class='status-error'>✗</span> Error en consulta: <br>";
        echo htmlspecialchars($e->getMessage()) . "<br>";
        echo htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    // 5. Verificar permisos de usuario autenticado
    echo "<h2>Verificando Autenticación...</h2>";
    
    try {
        // Obtener cualquier usuario admin
        $admin = \App\Models\User::where('role', 'admin')->withoutTrashed()->first();
        if ($admin) {
            echo "<div class='check'><span class='status-ok'>✓</span> Usuario Admin encontrado: " . $admin->name . "</div>";
        } else {
            echo "<div class='warning'>ℹ No hay usuarios admin sin soft-delete</div>";
        }
        
        // Verificar técnicos
        $technicians = \App\Models\User::withoutTrashed()
            ->where('role', 'technician')
            ->where('blocked', false)
            ->get();
        echo "<div class='check'><span class='status-ok'>✓</span> Técnicos encontrados: " . count($technicians) . "</div>";
    } catch (Exception $e) {
        echo "<div class='error'><span class='status-error'>✗</span> Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 6. Ver últimas líneas del log
    echo "<h2>Últimas líneas del archivo de log...</h2>";
    $log_file = $base_path . '/storage/logs/laravel.log';
    if (file_exists($log_file)) {
        $lines = file($log_file);
        $last_lines = array_slice($lines, -30);
        echo "<pre>";
        foreach ($last_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<div class='warning'>ℹ Archivo de log no existe</div>";
    }
    
} catch (Throwable $e) {
    echo "<div class='error'><span class='status-error'>✗ ERROR CRÍTICO:</span><br>";
    echo htmlspecialchars($e->getMessage()) . "<br>";
    echo htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
