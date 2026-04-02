<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Product, Category};

class ProductController extends Controller
{
    public function index(Request $r) {
        $query = Product::with('category')
                        ->where(function($q) {
                            $q->where('barcode', '!=', 'RECHARGE')
                              ->orWhereNull('barcode');
                        })
                        ->orderBy('description');
        
        $stock = $r->get('stock');
        $lowOnly = $r->boolean('low_only');
        if ($stock !== null && $stock !== '') {
            if ($lowOnly) {
                $query->where('stock_qty', '>=', 1)
                      ->where('stock_qty', '<=', (int)$stock);
            } else {
                $query->where('stock_qty', '<=', $stock);
            }
        }
        
        $search = $r->get('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('category', function($cat) use ($search) {
                      $cat->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $categoryFilter = $r->get('category');
        if ($categoryFilter) {
            $query->where('category_id', $categoryFilter);
        }
        
        $products = $query->paginate(20)->appends($r->only(['stock', 'search', 'category','low_only']));
        // Colecciones para paneles informativos (limitadas para rendimiento)
        $outOfStock = Product::with('category')
            ->where('stock_qty', 0)
            ->where(function($q) {
                $q->where('barcode', '!=', 'RECHARGE')
                  ->orWhereNull('barcode');
            })
            ->orderBy('description')
            ->limit(50)
            ->get();
        $lowStock = Product::with('category')
            ->where('stock_qty', '>', 0)
            ->where('stock_qty', '<=', 2)
            ->where(function($q) {
                $q->where('barcode', '!=', 'RECHARGE')
                  ->orWhereNull('barcode');
            })
            ->orderBy('stock_qty')
            ->orderBy('description')
            ->limit(50)
            ->get();
        $categories = Category::orderBy('name')->get();
        return view('products.index', compact('products','categories','stock','search','categoryFilter','outOfStock','lowStock'));
    }

    public function store(Request $r) {
        $data = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'description'=>'required',
            'stock_qty'=>'required|integer|min:0',
            'unit_cost'=>'required|numeric|min:0',
            'sale_price'=>'required|numeric|min:0',
            'barcode'=>'nullable'
        ]);
        Product::create($data);
        return back()->with('ok','Producto agregado');
    }

    public function update(Request $r, Product $product) {
        $data = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'description'=>'required',
            'stock_qty'=>'required|integer|min:0',
            'unit_cost'=>'required|numeric|min:0',
            'sale_price'=>'required|numeric|min:0',
            'barcode'=>'nullable'
        ]);
        $product->update($data);
        return back()->with('ok','Producto actualizado');
    }

    public function destroy(Product $product) {
        // Eliminar primero los sale_items relacionados para evitar error de integridad
        $product->saleItems()->delete();
        $product->delete();
        return back()->with('ok','Producto eliminado');
    }
}
