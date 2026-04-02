<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    public function create()
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')
            ->first();
        return view('cash.open', compact('session'));
    }

    public function store(Request $r)
    {
        $r->validate(['base_amount'=>'required|numeric|min:0']);
        $session = CashSession::whereDate('date', now()->toDateString())->whereNull('close_at')->first();
        if ($session) {
            $session->update(['base_amount' => (float)$r->base_amount]);
            return back()->with('ok','Base de caja actualizada');
        }
        CashSession::create([
            'date'=>now()->toDateString(),
            'base_amount'=>(float)$r->base_amount,
            'opened_by'=>auth()->id(),
            'open_at'=>now()
        ]);
        return redirect()->route('pos.index')->with('ok','Caja abierta');
    }

    public function closeSummary()
    {
        $session = CashSession::whereDate('date', now()->toDateString())->whereNull('close_at')->firstOrFail();
        
        $sales = \App\Models\Sale::whereDate('created_at', now()->toDateString())
            ->where('pending_delete', false)
            ->with('items.product')
            ->get();

        $soldSummary = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $it) {
                $key = $it->product->description ?? 'Producto';
                if (!isset($soldSummary[$key])) {
                    $soldSummary[$key] = ['qty' => 0, 'total' => 0.0];
                }
                $soldSummary[$key]['qty'] += (int)($it->quantity ?? 0);
                $line = $it->subtotal ?? (($it->unit_price ?? 0) * ($it->quantity ?? 0));
                $soldSummary[$key]['total'] += (float)$line;
            }
        }
        
        $repairs = \App\Models\Repair::whereDate('delivered_at', now()->toDateString())
            ->where('status', 'delivered')
            ->with('receivedBy', 'technician')
            ->get();
        
        $repairsReceived = \App\Models\Repair::whereDate('created_at', now()->toDateString())
            ->with('receivedBy', 'technician')
            ->get();
        
        $movements = \App\Models\CashMovement::where('cash_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $totalSalesEfectivo = 0;
        $totalSalesVirtual = 0;
        foreach ($sales as $sale) {
            $totalSalesEfectivo += (float)($sale->payment_cash ?? 0);
            $totalSalesVirtual += (float)($sale->payment_virtual ?? 0);
        }
        
        $totalRepairsEfectivo = 0;
        $totalRepairsVirtual = 0;
        foreach ($movements as $mov) {
            if ($mov->type === 'ingreso' && str_contains($mov->description ?? '', 'Reparación')) {
                if (str_contains($mov->description, '(Efectivo)')) {
                    $totalRepairsEfectivo += $mov->amount;
                } elseif (str_contains($mov->description, '(Virtual)')) {
                    $totalRepairsVirtual += $mov->amount;
                }
            }
        }
        
        $repairDeposits = $movements->where('type', 'deposit');
        $totalDepositsCash = $repairDeposits->where('payment_method', 'cash')->sum('amount');
        $totalDepositsVirtual = $repairDeposits->where('payment_method', 'virtual')->sum('amount');
        $totalDeposits = $repairDeposits->sum('amount');
        
        $ingresosOtros = 0;
        $egresos = 0;
        foreach ($movements as $mov) {
            if ($mov->type === 'ingreso' && !str_contains($mov->description ?? '', 'Reparación')) {
                $ingresosOtros += $mov->amount;
            } elseif ($mov->type === 'egreso') {
                $egresos += $mov->amount;
            }
        }
        $ingresos = $movements->whereIn('type', ['ingreso'])->sum('amount');
        
        // Calcular recargas (ventas con total = 0)
        $recargas = $sales->where('total', 0)->sum(function($s) {
            return (float)($s->payment_cash ?? 0) + (float)($s->payment_virtual ?? 0);
        });
        
        // Calcular comisiones si la columna existe
        $comisionEfectivo = 0;
        $comisionVirtual = 0;
        $comisionTotal = 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('sales', 'commission_amount')) {
            foreach ($sales as $s) {
                $comm = (float)($s->commission_amount ?? 0);
                if ($comm > 0) {
                    $comisionTotal += $comm;
                    $cm = $s->commission_method ?? '';
                    if ($cm === 'cash') {
                        $comisionEfectivo += $comm;
                    } elseif ($cm === 'virtual') {
                        $comisionVirtual += $comm;
                    }
                }
            }
        }
        
        $efectivoTotal = $totalSalesEfectivo + $totalRepairsEfectivo + $totalDepositsCash + $ingresosOtros;
        $virtualTotal = $totalSalesVirtual + $totalRepairsVirtual + $totalDepositsVirtual;
        $totalVendido = $totalSalesEfectivo + $totalSalesVirtual + $totalRepairsEfectivo + $totalRepairsVirtual;
        $enCaja = $session->base_amount + $efectivoTotal - $egresos;
        
        return view('cash.close-summary', compact(
            'session',
            'sales',
            'repairs',
            'repairsReceived',
            'movements',
            'soldSummary',
            'totalSalesEfectivo',
            'totalSalesVirtual',
            'totalRepairsEfectivo',
            'totalRepairsVirtual',
            'repairDeposits',
            'totalDeposits',
            'totalDepositsCash',
            'totalDepositsVirtual',
            'ingresos',
            'egresos',
            'efectivoTotal',
            'virtualTotal',
            'totalVendido',
            'enCaja',
            'recargas',
            'comisionEfectivo',
            'comisionVirtual',
            'comisionTotal'
        ));
    }

    public function close(Request $r)
    {
        $session = CashSession::whereDate('date', now()->toDateString())->whereNull('close_at')->firstOrFail();
        $session->update(['closed_by'=>auth()->id(),'close_at'=>now()]);
        $user = $r->user();
        if ($user && $user->role === 'admin') {
            return redirect()->route('dashboard')->with('ok','Caja cerrada');
        }
        return redirect()->route('pos.index')->with('ok','Caja cerrada');
    }

    public function addMovement(Request $r)
    {
        $r->validate([
            'type' => 'required|in:ingreso,egreso',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:cash,virtual',
            'egreso_type' => 'nullable|in:tienda,st',
        ]);

        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')->first();
        if (!$session) {
            return back()->with('err','No hay caja abierta hoy.');
        }

        $description = $r->description;
        // Agregar marcador de categoría si es egreso
        if ($r->type === 'egreso' && $r->egreso_type) {
            $description = ($r->egreso_type === 'st' ? '[ST] ' : '[TIENDA] ') . $description;
        }

        \App\Models\CashMovement::create([
            'cash_session_id' => $session->id,
            'type' => $r->type,
            'amount' => (float) $r->amount,
            'description' => $description,
            'payment_method' => $r->payment_method,
            'created_at' => now(),
        ]);

        return back()->with('ok','Movimiento registrado');
    }

    public function destroyMovement(\App\Models\CashMovement $movement, Request $r)
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')->first();
        if (!$session || $movement->cash_session_id !== $session->id) {
            return back()->with('err','No se puede eliminar: no pertenece a la sesión abierta.');
        }
        $movement->delete();
        return back()->with('ok','Movimiento eliminado');
    }

    public function addDeposit(Request $r)
    {
        $r->validate([
            'customer_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,virtual',
        ]);

        $session = CashSession::whereDate('date', now()->toDateString())
            ->whereNull('close_at')->first();
        if (!$session) {
            return back()->with('err','No hay caja abierta hoy.');
        }

        $method = $r->payment_method === 'cash' ? 'Efectivo' : 'Virtual';
        $description = "Abono - {$r->customer_name} ({$method})";

        try {
            \App\Models\CashMovement::create([
                'cash_session_id' => $session->id,
                'type' => 'deposit',
                'amount' => (float) $r->amount,
                'description' => $description,
                'payment_method' => $r->payment_method,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('err', 'No se pudo registrar el abono. Asegúrate de ejecutar el SQL manual para agregar la columna payment_method en la tabla cash_movements.');
        }

        return back()->with('ok','Abono registrado correctamente');
    }
}
