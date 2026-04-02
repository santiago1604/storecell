<?php
/**
 * VERIFICACIÓN RÁPIDA DE BD - Error 500 en /repairs
 * Ejecuta este archivo accediendo a: https://storecell.unaux.com/check_db.php
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificación BD - POS Tienda</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #007bff; padding-left: 10px; }
        .check { margin: 15px 0; padding: 10px; border-left: 4px solid #28a745; background: #f0fff4; }
        .error { margin: 15px 0; padding: 10px; border-left: 4px solid #dc3545; background: #fff5f5; }
        .warning { margin: 15px 0; padding: 10px; border-left: 4px solid #ffc107; background: #fffbf0; }
        .info { margin: 15px 0; padding: 10px; border-left: 4px solid #17a2b8; background: #f0f7ff; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🗄️ Verificación de Base de Datos</h1>";

$base_path = dirname(__DIR__);

// 1. Leer .env
echo "<h2>1. Leyendo archivo .env</h2>";
$env_file = $base_path . '/.env';
if (!file_exists($env_file)) {
    echo "<div class='error'>✗ Archivo .env no encontrado</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='check'>✓ Archivo .env encontrado</div>";

// Parsear .env
$env_vars = [];
$lines = file($env_file);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $env_vars[trim($key)] = trim($value);
}

// Mostrar variables de BD
echo "<h2>2. Variables de Base de Datos</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valor</th></tr>";
foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
    $val = $env_vars[$key] ?? 'NO DEFINIDA';
    echo "<tr><td><code>$key</code></td><td><code>$val</code></td></tr>";
}
echo "</table>";

// 2. Intentar conexión
echo "<h2>3. Intentando conexión a MySQL</h2>";

$host = $env_vars['DB_HOST'] ?? 'localhost';
$port = $env_vars['DB_PORT'] ?? '3306';
$user = $env_vars['DB_USERNAME'] ?? 'root';
$pass = $env_vars['DB_PASSWORD'] ?? '';
$db = $env_vars['DB_DATABASE'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<div class='check'>✓ Conexión a MySQL exitosa</div>";
    
    // Verificar BD
    echo "<h2>4. Verificando Base de Datos</h2>";
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='check'>✓ Base de datos <code>$db</code> existe</div>";
        
        // Conectar a la BD
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Verificar tablas
        echo "<h2>5. Tablas en la Base de Datos</h2>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Total de tablas: " . count($tables) . "</p>";
        
        $important_tables = ['users', 'repairs', 'sales', 'products', 'cash_sessions', 'sale_items'];
        echo "<table>";
        echo "<tr><th>Tabla</th><th>Estado</th></tr>";
        foreach ($important_tables as $table) {
            if (in_array($table, $tables)) {
                // Contar registros
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<tr><td><code>$table</code></td><td><span style='color: green;'>✓ Existe ($count registros)</span></td></tr>";
            } else {
                echo "<tr><td><code>$table</code></td><td><span style='color: red;'>✗ NO EXISTE</span></td></tr>";
            }
        }
        echo "</table>";
        
        // Verificar estructura de repairs
        echo "<h2>6. Estructura de la Tabla 'repairs'</h2>";
        if (in_array('repairs', $tables)) {
            $stmt = $pdo->query("DESCRIBE repairs");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><code>" . $col['Field'] . "</code></td>";
                echo "<td><code>" . $col['Type'] . "</code></td>";
                echo "<td>" . ($col['Null'] === 'YES' ? 'Sí' : 'No') . "</td>";
                echo "<td>" . ($col['Key'] ? $col['Key'] : '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Intentar consulta similar a la del controlador
            echo "<h2>7. Prueba de Consulta</h2>";
            try {
                $stmt = $pdo->prepare("
                    SELECT r.*, u.name as received_by_name, t.name as technician_name
                    FROM repairs r
                    LEFT JOIN users u ON r.received_by_user_id = u.id AND u.deleted_at IS NULL
                    LEFT JOIN users t ON r.technician_id = t.id AND t.deleted_at IS NULL
                    ORDER BY r.created_at DESC
                    LIMIT 1
                ");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<div class='check'>✓ Consulta ejecutada exitosamente</div>";
                if ($result) {
                    echo "<h3>Primer registro encontrado:</h3>";
                    echo "<pre>";
                    print_r($result);
                    echo "</pre>";
                } else {
                    echo "<div class='warning'>ℹ No hay registros en la tabla repairs</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Error en consulta: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>✗ Base de datos <code>$db</code> NO existe</div>";
        echo "<p>Bases de datos disponibles:</p>";
        $stmt = $pdo->query("SHOW DATABASES");
        $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($dbs as $d) {
            echo "<code>$d</code> ";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>
</body>
</html>";
?>
