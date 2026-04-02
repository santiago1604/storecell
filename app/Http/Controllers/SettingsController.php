<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('pos.index')->with('err', 'Acceso denegado');
        }

        $logo = Setting::get('logo_path');
        $storeName = Setting::get('store_name', 'POS Tienda');

        return view('admin.settings', compact('logo', 'storeName'));
    }

    public function update(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('pos.index')->with('err', 'Acceso denegado');
        }

        $request->validate([
            'store_name' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Guardar nombre de la tienda
        if ($request->filled('store_name')) {
            Setting::set('store_name', $request->store_name);
        }

        // Guardar logo directamente en htdocs/storage/logos/
        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            $oldLogo = Setting::get('logo_path');
            if ($oldLogo) {
                // En desarrollo: base_path('public/storage/...')
                // En producción (htdocs): base_path('htdocs/storage/...')
                $publicDir = file_exists(base_path('htdocs')) ? 'htdocs' : 'public';
                $oldPath = base_path($publicDir . '/storage/' . $oldLogo);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Guardar nuevo logo
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Detectar si estamos en htdocs (producción) o public (desarrollo)
            $publicDir = file_exists(base_path('htdocs')) ? 'htdocs' : 'public';
            $storagePath = base_path($publicDir . '/storage/logos');
            
            // Crear directorio si no existe
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            
            // Mover archivo
            $file->move($storagePath, $filename);
            
            Setting::set('logo_path', 'logos/' . $filename);
        }

        // Opción para eliminar logo
        if ($request->has('remove_logo') && $request->remove_logo == '1') {
            $oldLogo = Setting::get('logo_path');
            if ($oldLogo) {
                // Detectar si estamos en htdocs (producción) o public (desarrollo)
                $publicDir = file_exists(base_path('htdocs')) ? 'htdocs' : 'public';
                $oldPath = base_path($publicDir . '/storage/' . $oldLogo);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            Setting::set('logo_path', null);
        }

        return redirect()->route('settings.index')->with('ok', 'Configuración actualizada correctamente');
    }
}
