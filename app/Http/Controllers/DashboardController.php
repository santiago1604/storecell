<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\{Sale, SaleItem};

class DashboardController extends Controller
{
    public function index() {
        // Filtros de periodo para análisis
        $period = request('period', 'today'); // today, week, month, custom, specific
        $from = request('from');
        $to = request('to');
        $specificDate = request('date'); // Para consultar un día específico

        // Determinar rango de fechas según periodo seleccionado
        if ($period === 'specific' && $specificDate) {
            $from = $specificDate;
            $to = $specificDate;
        } elseif ($period === 'today') {
            $from = now()->toDateString();
            $to = now()->toDateString();
        } elseif ($period === 'week') {
            $from = now()->startOfWeek()->toDateString();
            $to = now()->endOfWeek()->toDateString();
        } elseif ($period === 'month') {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
        } elseif ($period === 'custom') {
            $from = $from ?: now()->subDays(7)->toDateString();
            $to = $to ?: now()->toDateString();
        }

        // Resumen del período seleccionado
        $periodBuilder = Sale::query();
        if ($from) { $periodBuilder->whereDate('created_at', '>=', $from); }
        if ($to) { $periodBuilder->whereDate('created_at', '<=', $to); }

        $summary = (clone $periodBuilder)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(total),0) as total, COALESCE(SUM(payment_cash),0) as cash, COALESCE(SUM(payment_virtual),0) as virtual')
            ->first();
        
        $ticket = ($summary->n ?? 0) ? ($summary->total / $summary->n) : 0;

        // Top 10 productos del mes
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $top = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', '>=', $monthStart)
            ->whereDate('sales.created_at', '<=', $monthEnd)
            ->select('products.description', DB::raw('SUM(sale_items.quantity) as qty'))
            ->groupBy('products.description')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        // Resumen de última sesión de caja (abierta o cerrada hoy)
        $session = \App\Models\CashSession::whereDate('date', now()->toDateString())
            ->orderByDesc('id')->first();
        $sum = null;
        $mov = null;
        $enCaja = 0;
        $movements = collect();
        $soldProducts = collect();
        
        if ($session) {
            $sum = Sale::where('cash_session_id', $session->id)
                ->selectRaw('COALESCE(SUM(payment_cash),0) as cash, COALESCE(SUM(payment_virtual),0) as virtual, COALESCE(SUM(total),0) as total')
                ->first();
            $mov = \App\Models\CashMovement::where('cash_session_id', $session->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type='ingreso' THEN amount END),0) as ing, COALESCE(SUM(CASE WHEN type='egreso' THEN amount END),0) as egr")
                ->first();
            $enCaja = ($session->base_amount ?? 0) + ($sum->cash ?? 0) + ($mov->ing ?? 0) - ($mov->egr ?? 0);

            // Listado de movimientos detallados
            $movements = \App\Models\CashMovement::where('cash_session_id', $session->id)
                ->orderBy('created_at')
                ->get(['id', 'type', 'amount', 'description', 'created_at']);

            // Productos vendidos del día (por sesión)
            $soldProducts = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.cash_session_id', $session->id)
                ->select('products.description', DB::raw('SUM(sale_items.quantity) as qty'), DB::raw('SUM(sale_items.subtotal) as total'))
                ->groupBy('products.description')
                ->orderByDesc('qty')
                ->get();
        }

        // Ventas del período para tabla
        $periodSales = $periodBuilder
            ->with(['items.product', 'user'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('admin.dashboard', compact(
            'period', 'from', 'to', 'specificDate', 'summary', 'ticket', 'top',
            'session', 'sum', 'mov', 'enCaja', 'movements', 'soldProducts',
            'periodSales'
        ));
    }
}
