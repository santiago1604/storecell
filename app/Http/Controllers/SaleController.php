<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\{Product, Sale, SaleItem, CashSession, CashMovement};
use Illuminate\Database\Eloquent\Builder;

class SaleController extends Controller
{
    public function sales()
    {
        // Ventas de hoy
        $salesToday = Sale::with(['items.product.category', 'user', 'requestedBy'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->get();
        
        // Ventas de otros días (últimas 50, excluyendo hoy)
        $salesOtherDays = Sale::with(['items.product.category', 'user', 'requestedBy'])
            ->whereDate('created_at', '<', now()->toDateString())
            ->orderByDesc('id')
            ->limit(50)
            ->get();
        
        // Obtener reparaciones entregadas
        $repairs = \App\Models\Repair::with(['receivedBy', 'technician'])
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->limit(50)
            ->get();
        
        return view('pos.sales', compact('salesToday', 'salesOtherDays', 'repairs'));
    }
    public function index()
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')
            ->first();

        $items = session('cart', []);

        $summary = $session
            ? $this->sessionNumbers($session)
            : ['base'=>0,'efectivo'=>0,'virtual'=>0,'total'=>0,'en_caja'=>0];

        $products = Product::where('active',1)->orderBy('description')->get();
        $movements = $session
            ? CashMovement::where('cash_session_id', $session->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id','type','amount','description','created_at'])
            : collect();

        return view('pos.index', compact('session','items','summary','products','movements'));
    }

    // --- tus métodos existentes ---

    public function requestDelete(\App\Models\Sale $sale, Request $r)
    {
        // Vendedor/Técnico solicita eliminar una venta
        if (!in_array($r->user()->role, ['seller', 'technician', 'admin'])) {
            return back()->withErrors(['permission' => 'No tienes permisos']);
        }

        if ($sale->pending_delete) {
            return back()->with('err', 'Esta venta ya tiene una solicitud de eliminación pendiente');
        }

        $sale->update([
            'pending_delete' => true,
            'requested_by' => $r->user()->id,
            'delete_requested_at' => now(),
        ]);

        return back()->with('ok', 'Solicitud de eliminación enviada. Esperando aprobación del administrador.');
    }

    public function cancelDeleteRequest(\App\Models\Sale $sale, Request $r)
    {
        // Cancelar solicitud de eliminación
        $sale->update([
            'pending_delete' => false,
            'requested_by' => null,
            'delete_requested_at' => null,
        ]);

        return back()->with('ok', 'Solicitud de eliminación cancelada');
    }

    public function destroySale(\App\Models\Sale $sale, Request $r)
    {
        // Solo admin puede eliminar
        if ($r->user()->role !== 'admin') {
            return back()->withErrors(['permission' => 'Solo el administrador puede eliminar ventas']);
        }

        // Reponer stock de los productos vendidos
        foreach ($sale->items as $item) {
            $product = $item->product ?? Product::find($item->product_id);
            if ($product) {
                $product->stock_qty += (int) $item->quantity;
                $product->save();
            }
        }
        $sale->items()->delete();
        $sale->delete();
        return back()->with('ok','Venta eliminada y stock repuesto');
    }

    public function addItem(Request $r)
    {
        $prod = Product::findOrFail($r->product_id);
        $qty = max(1, (int)$r->quantity);

        if ($qty > $prod->stock_qty) {
            return back()->with('err', 'Stock insuficiente');
        }

        $cart = session('cart', []);
        $cart[] = [
            'product_id' => $prod->id,
            'description'=> $prod->description,
            'qty'        => $qty,
            'unit_price' => $prod->sale_price,
            'subtotal'   => $qty * $prod->sale_price,
        ];
        session(['cart'=>$cart]);

        return back();
    }

    public function addRecharge(Request $r)
    {
        $r->validate([
            'recharge_description' => 'required|string|max:255',
            'recharge_amount' => 'required|numeric|min:0.01',
        ]);

        $desc = trim($r->input('recharge_description'));
        $amount = (float)$r->input('recharge_amount');

        $cart = session('cart', []);
        $cart[] = [
            'product_id' => null,
            'description'=> $desc,
            'qty'        => 1,
            'unit_price' => $amount,
            'subtotal'   => $amount,
            'is_recharge'=> true,
        ];
        session(['cart'=>$cart]);
        return back()->with('ok','Recarga añadida al carrito');
    }

    public function removeItem(Request $r)
    {
        $index = (int)$r->input('index', -1);
        $cart = session('cart', []);

        if ($index >= 0 && $index < count($cart)) {
            array_splice($cart, $index, 1);
            session(['cart' => $cart]);
            return back()->with('ok','Producto eliminado');
        }
        return back()->with('err','Ítem no encontrado');
    }

    public function checkout(Request $r)
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')->firstOrFail();

        $cart = session('cart', []);
        if (empty($cart)) {
            return back()->with('err','Carrito vacío');
        }

        $total = collect($cart)->sum('subtotal');
        $payType = $r->input('pay_type','cash');

        // Datos de gateway (Bold/Sistecrédito) - leer antes de validar pagos
        $gatewayType = $r->input('gateway_type'); // 'bold' | 'sistecredito'
        $gatewayIdx = $r->input('gateway_item_indexes', []);
        $gatewayFeeMethod = $r->input('gateway_fee_payment_method', 'virtual'); // método de pago de la comisión
        $gatewayActive = in_array($gatewayType, ['bold','sistecredito']) && !empty($gatewayIdx);

        if ($payType === 'cash') {
            $cash = $total; $virtual = 0;
        } elseif ($payType === 'virtual') {
            $cash = 0; $virtual = $total;
        } else { // mixed
            $cash = max(0, (float)$r->input('payment_cash',0));
            $virtual = max(0, (float)$r->input('payment_virtual',0));
            
            // Si NO hay gateway activo, validar que coincidan los montos
            if (!$gatewayActive && round($cash + $virtual, 2) !== round($total, 2)) {
                return back()->with('err', 'Los montos de efectivo y virtual no coinciden con el total.');
            }
        }
        $commissionPct = 5.0; // fijo por requerimiento
        $commissionBase = 0.0;
        if (in_array($gatewayType, ['bold','sistecredito'])) {
            foreach ($gatewayIdx as $i) {
                $ii = (int)$i;
                if (isset($cart[$ii])) {
                    $commissionBase += (float)($cart[$ii]['subtotal'] ?? 0);
                }
            }
        }
        $commission = round($commissionBase * ($commissionPct/100), 2);

        // Si hay gateway activo, el pago principal llega automáticamente por virtual
        // y la comisión se registra en una venta separada (no sumar aquí para evitar doble conteo)
        $actualCash = $cash;
        $actualVirtual = $virtual;
        
        if ($commission > 0 && in_array($gatewayType, ['bold','sistecredito'])) {
            // El pago principal (total del carrito) llega automáticamente por virtual
            $actualVirtual = $total;
            $actualCash = 0;
            // No sumar comisión aquí: se crea venta separada -COM
        }

        DB::transaction(function() use ($cart,$total,$actualCash,$actualVirtual,$session,$gatewayType,$commission,$gatewayFeeMethod) {
            $sale = Sale::create([
                'user_id'         => auth()->id(),
                'cash_session_id' => $session->id,
                'sale_number'     => now()->format('YmdHis').'-'.auth()->id(),
                'total'           => $total,
                'gateway_type'    => in_array($gatewayType, ['bold','sistecredito']) ? $gatewayType : null,
                'commission_amount' => $commission,
                'commission_method' => ($commission > 0 && in_array($gatewayType, ['bold','sistecredito'])) ? ($gatewayFeeMethod === 'cash' ? 'cash' : 'virtual') : null,
                'payment_cash'    => $actualCash,
                'payment_virtual' => $actualVirtual,
                'created_at'      => now(),
            ]);

            foreach ($cart as $it) {
                // Resolver product_id: si es una recarga, usar producto placeholder
                $productId = $it['product_id'] ?? null;
                $isRecharge = !empty($it['is_recharge']);
                if ($productId === null && $isRecharge) {
                    $productId = $this->getRechargeProductId();
                }

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $productId,
                    'description'=> $it['description'] ?? null,
                    'quantity'   => $it['qty'],
                    'unit_price' => $it['unit_price'],
                    'subtotal'   => $it['subtotal'],
                ]);

                if (!empty($productId) && !$isRecharge) {
                    Product::where('id', $productId)
                        ->decrement('stock_qty', $it['qty']);
                }
            }

            // Crear venta separada solo para la comisión como "Comisión venta BOLD/SISTECREDITO"
            if ($commission > 0 && in_array($gatewayType, ['bold','sistecredito'])) {
                $saleNumberCom = $sale->sale_number.'-COM';
                $commissionCash = $gatewayFeeMethod === 'cash' ? $commission : 0;
                $commissionVirtual = $gatewayFeeMethod === 'virtual' ? $commission : 0;

                Sale::create([
                    'user_id'         => auth()->id(),
                    'cash_session_id' => $session->id,
                    'sale_number'     => $saleNumberCom,
                    'total'           => 0, // venta de solo comisión, sin ítems
                    'gateway_type'    => $gatewayType,
                    'commission_amount' => $commission,
                    'commission_method' => $gatewayFeeMethod === 'cash' ? 'cash' : 'virtual',
                    'payment_cash'    => $commissionCash,
                    'payment_virtual' => $commissionVirtual,
                    'created_at'      => now(),
                ]);
            }
        });

        session()->forget('cart');
        return back()->with('ok','Venta registrada');
    }

    public function sessionSummary()
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')->firstOrFail();

        return response()->json($this->sessionNumbers($session));
    }

    public function search(Request $r)
    {
        $q = trim((string)$r->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $products = Product::query()
            ->with('category')
            ->where('active', 1)
            ->where(function(Builder $b) use ($q) {
                $b->where('description', 'like', "%$q%")
                  ->orWhere('barcode', 'like', "%$q%")
                  ->orWhereHas('category', function(Builder $c) use ($q) {
                      $c->where('name', 'like', "%$q%");
                  });
            })
            ->orderBy('description')
            ->limit(20)
            ->get(['id','description','sale_price','stock_qty','barcode','category_id']);

        $results = $products->map(function($p){
            return [
                'id'       => $p->id,
                'text'     => $p->description,
                'price'    => (float)$p->sale_price,
                'stock'    => (int)$p->stock_qty,
                'barcode'  => $p->barcode,
                'category' => optional($p->category)->name,
            ];
        });

        return response()->json($results);
    }

    private function sessionNumbers($session)
    {
        // Ventas de productos (POS)
        $sum = Sale::where('cash_session_id',$session->id)
            ->selectRaw('COALESCE(SUM(payment_cash),0) as cash, COALESCE(SUM(payment_virtual),0) as virtual, COALESCE(SUM(total),0) as total')
            ->first();

        // Movimientos de caja (incluye reparaciones y otros movimientos)
        $mov = CashMovement::where('cash_session_id',$session->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type='ingreso' THEN amount END),0) as ing,
                COALESCE(SUM(CASE WHEN type='egreso' THEN amount END),0) as egr
            ")->first();

        // Separar efectivo y virtual de los movimientos de caja
        // Los movimientos con "(Efectivo)" en description son efectivo
        // Los movimientos con "(Virtual)" en description son virtuales
        $movCash = CashMovement::where('cash_session_id',$session->id)
            ->where('type', 'ingreso')
            ->where('description', 'like', '%(Efectivo)%')
            ->sum('amount');

        $movVirtual = CashMovement::where('cash_session_id',$session->id)
            ->where('type', 'ingreso')
            ->where('description', 'like', '%(Virtual)%')
            ->sum('amount');

        // Depósitos/abonos estandarizados
        // Si existe la columna payment_method, usarla; si no, inferir por descripción
        if (Schema::hasColumn('cash_movements', 'payment_method')) {
            $depositCash = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('payment_method','cash')
                ->sum('amount');
            $depositVirtual = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('payment_method','virtual')
                ->sum('amount');
            // Egresos por método de pago
            $egresosCash = CashMovement::where('cash_session_id',$session->id)
                ->where('type','egreso')
                ->where('payment_method','cash')
                ->sum('amount');
            $egresosVirtual = CashMovement::where('cash_session_id',$session->id)
                ->where('type','egreso')
                ->where('payment_method','virtual')
                ->sum('amount');
        } else {
            $depositCash = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('description', 'like', '%(Efectivo)%')
                ->sum('amount');
            $depositVirtual = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('description', 'like', '%(Virtual)%')
                ->sum('amount');
            $egresosCash = 0;
            $egresosVirtual = 0;
        }

        // Total efectivo = ventas en efectivo + ingresos de efectivo de reparaciones - egresos en efectivo
    $totalEfectivo = ($sum->cash ?? 0) + $movCash + $depositCash - $egresosCash;
        
        // Total virtual = ventas virtuales + ingresos virtuales de reparaciones - egresos virtuales
    $totalVirtual = ($sum->virtual ?? 0) + $movVirtual + $depositVirtual - $egresosVirtual;
        
        // Total vendido = ventas de productos + ingresos de reparaciones
        $totalVendido = ($sum->total ?? 0) + ($mov->ing ?? 0);

        // En caja solo cuenta el efectivo (base + efectivo de ventas + movimientos efectivo - egresos)
        // En caja: no suma depósitos virtuales, sí suma abonos en efectivo
        $enCaja = $session->base_amount + $totalEfectivo + ($mov->ing - $movCash - $movVirtual) - ($mov->egr ?? 0);

        // Total de recargas del día
        $rechargeProductId = $this->getRechargeProductId();
        $rechargesTotal = SaleItem::whereHas('sale', function($q) use ($session) {
                $q->where('cash_session_id', $session->id);
            })
            ->where('product_id', $rechargeProductId)
            ->sum('subtotal');

        // Ventas POS (incluye productos y recargas dentro de las ventas registradas)
        $ventasPOS = ($sum->total ?? 0);

        // Abonos iniciales de reparaciones (tipo deposit) separados por método
        if (Schema::hasColumn('cash_movements', 'payment_method')) {
            $repairDepositCash = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('payment_method','cash')
                ->where('description','like','Abono reparación%')
                ->sum('amount');
            $repairDepositVirtual = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('payment_method','virtual')
                ->where('description','like','Abono reparación%')
                ->sum('amount');
        } else {
            $repairDepositCash = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('description','like','Abono reparación%')
                ->where('description','like','%(Efectivo)%')
                ->sum('amount');
            $repairDepositVirtual = CashMovement::where('cash_session_id',$session->id)
                ->where('type','deposit')
                ->where('description','like','Abono reparación%')
                ->where('description','like','%(Virtual)%')
                ->sum('amount');
        }

        // Total ST (Servicio Técnico) = ingresos de reparaciones entregadas + abonos iniciales
        $totalST = $movCash + $movVirtual + $repairDepositCash + $repairDepositVirtual;

        // Total general de ventas (ventas POS + ST) - recargas ya incluidas en ventas POS
        $totalVentas = $ventasPOS + $totalST;

        // Comisiones por método (ventas con gateway) solo si existen las columnas
        if (Schema::hasColumn('sales','commission_amount') && Schema::hasColumn('sales','commission_method')) {
            $comisionEfectivo = Sale::where('cash_session_id',$session->id)
                ->where('commission_method','cash')
                ->sum('commission_amount');
            $comisionVirtual = Sale::where('cash_session_id',$session->id)
                ->where('commission_method','virtual')
                ->sum('commission_amount');
            $comisionTotal = $comisionEfectivo + $comisionVirtual;
        } else {
            $comisionEfectivo = 0;
            $comisionVirtual = 0;
            $comisionTotal = 0;
        }

        // Egresos separados por tienda y ST usando marcadores
        $egresosTienda = CashMovement::where('cash_session_id',$session->id)
            ->where('type','egreso')
            ->where(function($q) {
                $q->where('description', 'like', '[TIENDA]%')
                  ->orWhere(function($q2) {
                      $q2->where('description', 'not like', '[ST]%')
                         ->where('description', 'not like', '[TIENDA]%');
                  });
            })
            ->sum('amount');

        $egresosST = CashMovement::where('cash_session_id',$session->id)
            ->where('type','egreso')
            ->where('description', 'like', '[ST]%')
            ->sum('amount');

        return [
            'base'        => $session->base_amount,
            'efectivo'    => $totalEfectivo,
            'virtual'     => $totalVirtual,
            'ventas_pos'  => $ventasPOS,
            'recargas'    => $rechargesTotal,
            'total_st'    => $totalST,
            'total_ventas'=> $totalVentas,
            'egresos_tienda' => $egresosTienda,
            'egresos_st'    => $egresosST,
            'en_caja'    => $enCaja,
            'comision_efectivo' => $comisionEfectivo,
            'comision_virtual'  => $comisionVirtual,
            'comision_total'    => $comisionTotal,
        ];
    }

    private function getRechargeProductId(): int
    {
        // Buscar producto placeholder por barcode fijo
        $p = Product::where('barcode', 'RECHARGE')->first();
        if ($p) {
            // Asegurar que el stock siempre esté en 0 para este producto
            if ($p->stock_qty != 0) {
                $p->update(['stock_qty' => 0]);
            }
            return $p->id;
        }

        // Intentar asociar a una categoría 'Servicios' si existe; si no, crearla
        $categoryId = optional(\App\Models\Category::firstOrCreate(['name' => 'Servicios']))->id ?? null;

        $p = Product::create([
            'category_id' => $categoryId,
            'description' => 'Recarga/Servicio (Producto interno - No editar)',
            'stock_qty'   => 0,
            'unit_cost'   => 0,
            'sale_price'  => 0,
            'barcode'     => 'RECHARGE',
            'active'      => 0, // Marcarlo como inactivo para que no aparezca en el POS
        ]);
        return $p->id;
    }
}

