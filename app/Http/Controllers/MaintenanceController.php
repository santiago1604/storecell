<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function resetKeepUsers(Request $request)
    {
        // Seguridad adicional: solo admin
        if (!($request->user() && $request->user()->role === 'admin')) {
            abort(403);
        }

        $dbName = DB::getDatabaseName();
        // Obtener todas las tablas del esquema actual
        $tables = DB::select("SELECT TABLE_NAME as name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
        $skip = [
            'users',
            'migrations',
            // Tablas del sistema que no conviene truncar
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens',
            'sessions',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            $name = $t->name ?? (is_array($t) ? ($t['name'] ?? reset($t)) : reset((array)$t));
            if (!$name) { continue; }
            if (in_array($name, $skip)) { continue; }
            try {
                DB::statement("TRUNCATE TABLE `{$name}`");
            } catch (\Throwable $e) {
                // Intentar un borrado por delete como fallback
                try { DB::table($name)->delete(); } catch (\Throwable $ee) {}
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return back()->with('ok', 'Base de datos reiniciada (se conservaron los usuarios).');
    }
}
