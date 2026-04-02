<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $status = $r->input('status');
        
        $orders = Order::query()
            ->with('requestedBy')
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($r->query());
        
        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product_description' => 'required|string|max:500',
            'quantity' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
        ]);

        Order::create([
            'product_description' => $data['product_description'],
            'quantity' => $data['quantity'],
            'category_id' => $data['category_id'],
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'finalized' => 'no',
        ]);

        return back()->with('status', 'Pedido creado correctamente.');
    }

    public function update(Request $r, Order $order)
    {
        $data = $r->validate([
            'finalized' => 'required|in:no,si',
        ]);

        $order->update($data);
        return back()->with('status', 'Pedido actualizado.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('status', 'Pedido eliminado.');
    }

    public function importProducts(Order $order, Request $r)
    {
        if ($order->finalized !== 'si') {
            return back()->with('status', 'Primero marca el pedido como finalizado.');
        }

        $message = '';
        DB::transaction(function() use ($order, &$message) {
            // Buscar producto por descripción + categoría; si no existe, crearlo
            $product = Product::where('description', $order->product_description)
                ->where('category_id', $order->category_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->stock_qty += (int) $order->quantity;
                $product->save();
                $message = 'Stock actualizado en el producto existente y pedido eliminado.';
            } else {
                Product::create([
                    'category_id' => $order->category_id,
                    'description' => $order->product_description,
                    'stock_qty'   => (int) $order->quantity,
                    'unit_cost'   => 0,
                    'sale_price'  => 0,
                    'barcode'     => null,
                    'active'      => 1,
                ]);
                $message = 'Producto creado, stock cargado y pedido eliminado.';
            }

            // Eliminar el pedido para que salga de la lista
            $order->delete();
        });

        return back()->with('status', $message);
    }
}
