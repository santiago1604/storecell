<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\{CashSession, Sale, CashMovement};

class ReportController extends Controller
{
    public function exportSessionCsv(Request $request): StreamedResponse
    {
        $session = CashSession::whereDate('date', now()->toDateString())
            ->orderByDesc('id')->firstOrFail();

        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        $filename = 'reporte_sesion_'.now()->format('Ymd').'.csv';

        $callback = function() use ($session, $isAdmin) {
            $out = fopen('php://output', 'w');
            // Cabeceras
            if ($isAdmin) {
                fputcsv($out, ['Fecha','Producto','Cantidad','Precio unitario','Subtotal','Usuario','Pago efectivo','Pago virtual','Nro venta']);
            } else {
                fputcsv($out, ['Fecha','Descripción de producto vendido','Cantidad','Precio total','Egreso','Total virtual','Total efectivo','Total en caja']);
            }

            // Ventas e items
            $sales = Sale::with(['items.product','user'])
                ->where('cash_session_id',$session->id)
                ->orderBy('id')
                ->get();

            $sumCash = 0; $sumVirtual = 0; $sumEgreso = 0; $enCaja = $session->base_amount;

            foreach ($sales as $sale) {
                $sumCash += (float)$sale->payment_cash;
                $sumVirtual += (float)$sale->payment_virtual;
                foreach ($sale->items as $it) {
                    $desc = optional($it->product)->description ?? 'Producto #'.$it->product_id;
                    if ($isAdmin) {
                        fputcsv($out, [
                            $sale->created_at,
                            $desc,
                            (int)$it->quantity,
                            number_format((float)$it->unit_price,2,'.',''),
                            number_format((float)$it->subtotal,2,'.',''),
                            optional($sale->user)->email,
                            number_format((float)$sale->payment_cash,2,'.',''),
                            number_format((float)$sale->payment_virtual,2,'.',''),
                            $sale->sale_number,
                        ]);
                    } else {
                        fputcsv($out, [
                            $sale->created_at,
                            $desc,
                            (int)$it->quantity,
                            number_format((float)$it->subtotal,2,'.',''),
                            '', // Egreso se carga más abajo en bloque de movimientos
                            '', // Total virtual (se agrega al final)
                            '', // Total efectivo (se agrega al final)
                            '', // Total en caja (se agrega al final)
                        ]);
                    }
                }
            }

            // Movimientos
            $movs = CashMovement::where('cash_session_id',$session->id)
                ->orderBy('created_at')->get();
            foreach ($movs as $m) {
                if ($m->type === 'ingreso') {
                    $sumCash += (float)$m->amount; // ingreso aumenta caja
                } else {
                    $sumEgreso += (float)$m->amount; // egreso
                }
            }

            $enCaja = $session->base_amount + $sumCash - $sumEgreso; // efectivo en caja (no incluye virtual)

            // Líneas de totales para vendedor
            if (!$isAdmin) {
                fputcsv($out, []);
                fputcsv($out, ['','','','', 'Egreso', number_format($sumVirtual,2,'.',''), number_format($sumCash,2,'.',''), number_format($enCaja,2,'.','')]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
