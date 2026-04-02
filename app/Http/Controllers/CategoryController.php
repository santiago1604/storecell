<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Category};

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $r) {
        $r->validate(['name'=>'required']);
        $category = Category::create(['name'=>$r->name]);
        
        // Si es una petición AJAX (desde el modal), devolver JSON
        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Categoría creada correctamente'
            ]);
        }
        
        return back()->with('ok','Categoría agregada');
    }

    public function update(Request $r, Category $category) {
        $r->validate(['name'=>'required']);
        $category->update(['name'=>$r->name]);
        return back()->with('ok','Categoría actualizada');
    }

    public function destroy(Request $r, Category $category) {
        try {
            $category->delete();
            
            // Si es una petición AJAX (desde el modal), devolver JSON
            if ($r->expectsJson() || $r->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Categoría eliminada correctamente'
                ]);
            }
            
            return back()->with('ok','Categoría eliminada');
        } catch (\Exception $e) {
            if ($r->expectsJson() || $r->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar. La categoría tiene productos asociados.'
                ], 400);
            }
            
            return back()->withErrors(['error' => 'No se puede eliminar. La categoría tiene productos asociados.']);
        }
    }
}
