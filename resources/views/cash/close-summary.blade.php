@extends('layouts.app')
@section('content')
@php
    // Función helper para formato de moneda colombiana
    function formatCurrency($amount) {
        return '$ ' . number_format($amount, 0, ',', '.');
    }
@endphp
<div class="max-w-6xl mx-auto">
    <div class="bg-white border-2 border-gray-800 rounded-lg p-6 print:border-0">
        <!-- Encabezado -->
        <div class="text-center mb-6 border-b-2 border-gray-800 pb-4">
            <h1 class="text-3xl font-bold mb-2">RESUMEN DE CIERRE DE CAJA</h1>
            <h2 class="text-xl font-semibold">StoreCell</h2>
            <p class="text-lg mt-2">{{ now()->format('d/m/Y') }}</p>
        </div>

        <!-- Resumen Financiero Principal -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-600 rounded-lg p-6 mb-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-blue-900 mb-1">📋 Sesión de caja</h3>
                <p class="text-gray-600">{{ now()->format('d/m/Y') }}</p>
            </div>

            <!-- Totales -->
            <div>
                <h4 class="text-xl font-bold text-purple-700 mb-3">💵 TOTALES DEL DÍA</h4>
                <div class="grid grid-cols-2 gap-3 ml-4">
                    <div class="bg-white p-4 rounded-lg border-2 border-green-400">
                        <div class="text-sm text-gray-600 mb-1">Total Efectivo:</div>
                        <div class="text-2xl font-bold text-green-700">{!! formatCurrency($efectivoTotal) !!}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border-2 border-purple-400">
                        <div class="text-sm text-gray-600 mb-1">Total Virtual:</div>
                        <div class="text-2xl font-bold text-purple-700">{!! formatCurrency($virtualTotal) !!}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border-2 border-indigo-400">
                        <div class="text-sm text-gray-600 mb-1">Total Recargas:</div>
                        <div class="text-2xl font-bold text-indigo-700">{!! formatCurrency($recargas ?? 0) !!}</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-100 to-blue-50 p-4 rounded-lg border-2 border-blue-600">
                        <div class="text-sm text-gray-700 font-semibold mb-1">TOTAL VENTAS:</div>
                        <div class="text-2xl font-bold text-blue-900">{!! formatCurrency($totalVendido) !!}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border-2 border-red-400">
                        <div class="text-sm text-gray-600 mb-1">Total Egresos:</div>
                        <div class="text-2xl font-bold text-red-700">-{!! formatCurrency($egresos ?? 0) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VENTAS -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-blue-600 text-white p-2 rounded">📦 VENTAS DEL DÍA</h3>
            @if($sales->count() > 0)
                <div class="space-y-3">
                    @foreach($sales as $sale)
                        <div class="bg-gray-50 border border-gray-300 rounded p-3">
                            <div class="flex flex-wrap gap-2 justify-between items-center mb-2">
                                <span class="font-semibold">Venta #{{ $sale->id }}</span>
                                <span class="text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($sale->created_at)->format('H:i:s') }}</span>
                                <span class="font-bold text-lg">{!! formatCurrency($sale->total) !!}</span>
                                @php
                                    $pm = 'Desconocido'; $cls = 'bg-gray-100 text-gray-800';
                                    $cash = (float)($sale->payment_cash ?? 0);
                                    $virt = (float)($sale->payment_virtual ?? 0);
                                    if ($cash > 0 && $virt > 0) { $pm = 'Mixto'; $cls='bg-yellow-100 text-yellow-800'; }
                                    elseif ($cash > 0) { $pm='Efectivo'; $cls='bg-green-100 text-green-800'; }
                                    elseif ($virt > 0) { $pm='Virtual'; $cls='bg-purple-100 text-purple-800'; }
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $cls }}">{{ $pm }}</span>
                            </div>
                            <div class="text-sm text-gray-700 ml-4">
                                @foreach($sale->items as $item)
                                    <div class="flex justify-between">
                                        <span>• {{ $item->product->description ?? 'Producto' }} (x{{ $item->quantity }})</span>
                                        @php
                                            $lineTotal = $item->subtotal ?? ($item->unit_price * $item->quantity);
                                        @endphp
                                        <span>{!! formatCurrency($lineTotal) !!}</span>
                                    </div>
                                @endforeach
                                @php($cash=(float)($sale->payment_cash ?? 0))
                                @php($virt=(float)($sale->payment_virtual ?? 0))
                                @if($cash>0 && $virt>0)
                                    <div class="text-xs text-gray-600 text-right mt-1">
                                        Efectivo: {!! formatCurrency($cash) !!} | Virtual: {!! formatCurrency($virt) !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 bg-blue-100 p-3 rounded font-bold text-right">
                    <span>Subtotal Ventas Efectivo: {!! formatCurrency($totalSalesEfectivo) !!}</span> | 
                    <span>Virtual: {!! formatCurrency($totalSalesVirtual) !!}</span>
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No hay ventas registradas hoy.</p>
            @endif
        </div>

        <!-- COMISIONES BOLD/SISTECRÉDITO -->
        @if(($comisionTotal ?? 0) > 0)
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-pink-600 text-white p-2 rounded">💳 COMISIONES BOLD/SISTECRÉDITO</h3>
            <div class="bg-pink-50 p-4 rounded">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-white p-3 rounded border border-pink-300">
                        <div class="text-sm text-gray-600 mb-1">Efectivo</div>
                        <div class="font-bold text-lg text-pink-700">{!! formatCurrency($comisionEfectivo ?? 0) !!}</div>
                    </div>
                    <div class="bg-white p-3 rounded border border-pink-300">
                        <div class="text-sm text-gray-600 mb-1">Virtual</div>
                        <div class="font-bold text-lg text-pink-700">{!! formatCurrency($comisionVirtual ?? 0) !!}</div>
                    </div>
                    <div class="bg-pink-200 p-3 rounded border-2 border-pink-600">
                        <div class="text-sm text-gray-800 mb-1 font-semibold">Total</div>
                        <div class="font-bold text-xl text-pink-900">{!! formatCurrency($comisionTotal) !!}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- SERVICIOS TÉCNICOS -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-green-600 text-white p-2 rounded">🔧 SERVICIOS TÉCNICOS ENTREGADOS</h3>
            @if($repairs->count() > 0)
                <div class="space-y-3">
                    @foreach($repairs as $repair)
                        <div class="bg-gray-50 border border-gray-300 rounded p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold">Reparación #{{ $repair->id }}</span>
                                <span class="text-sm text-gray-600">{{ optional($repair->delivered_at)->format('H:i:s') }}</span>
                            </div>
                            <div class="text-sm text-gray-700 ml-4">
                                <div class="flex justify-between">
                                    <span>• Cliente: {{ $repair->customer_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>• Dispositivo: {{ $repair->device_description }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>• Problema: {{ $repair->issue_description }}</span>
                                </div>
                                @if($repair->parts_cost > 0)
                                    <div class="flex justify-between mt-1">
                                        <span>• Repuestos:</span>
                                        <span>{!! formatCurrency($repair->parts_cost) !!}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between font-bold mt-2 pt-2 border-t">
                                    <span>Total:</span>
                                    <span>{!! formatCurrency($repair->total_cost) !!}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 bg-green-100 p-3 rounded font-bold text-right">
                    <span>Subtotal Reparaciones Efectivo: {!! formatCurrency($totalRepairsEfectivo) !!}</span> | 
                    <span>Virtual: {!! formatCurrency($totalRepairsVirtual) !!}</span>
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No hay servicios técnicos entregados hoy.</p>
            @endif
        </div>

        <!-- REPARACIONES RECIBIDAS HOY -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-emerald-600 text-white p-2 rounded">📥 REPARACIONES RECIBIDAS HOY</h3>
            @if(isset($repairsReceived) && $repairsReceived->count() > 0)
                <div class="space-y-2">
                    @foreach($repairsReceived as $rr)
                        <div class="bg-gray-50 border border-gray-300 rounded p-3">
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold">#{{ $rr->id }} • {{ $rr->customer_name }}</span>
                                <span class="text-gray-600">{{ optional($rr->created_at)->format('H:i:s') }}</span>
                            </div>
                            <div class="text-xs text-gray-700 ml-4 mt-1">
                                <div>• Dispositivo: {{ $rr->device_description }}</div>
                                <div>• Problema: {{ $rr->issue_description }}</div>
                                <div>• Estado: <span class="font-semibold">{{ ucfirst($rr->status) }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 bg-emerald-100 p-3 rounded font-bold text-right">
                    Total recibidas hoy: {{ $repairsReceived->count() }}
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No se recibieron equipos hoy.</p>
            @endif
        </div>

        <!-- ABONOS / ANTICIPOS DE REPARACIONES -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-pink-600 text-white p-2 rounded">💵 ABONOS / ANTICIPOS</h3>
            @if(isset($repairDeposits) && $repairDeposits->count() > 0)
                <div class="space-y-2">
                    @foreach($repairDeposits as $m)
                        <div class="flex justify-between items-center bg-gray-50 border border-gray-300 rounded p-2 text-sm">
                            <span>{{ $m->description ?? 'Abono' }}</span>
                            <span class="text-gray-600">{{ optional($m->created_at)->format('H:i:s') }}</span>
                            <span class="font-bold text-emerald-700">+{!! formatCurrency($m->amount) !!}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 bg-pink-100 p-3 rounded font-bold">
                    <div class="flex justify-between">
                        <span>Total Abonos:</span>
                        <span>{!! formatCurrency($totalDeposits) !!}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1">
                        <span>Efectivo:</span>
                        <span>{!! formatCurrency($totalDepositsCash) !!}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Virtual:</span>
                        <span>{!! formatCurrency($totalDepositsVirtual) !!}</span>
                    </div>
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No se registraron abonos hoy.</p>
            @endif
        </div>

        <!-- MOVIMIENTOS DE CAJA -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-yellow-600 text-white p-2 rounded">💰 MOVIMIENTOS DE CAJA</h3>
            @if($movements->count() > 0)
                <div class="space-y-2">
                    @foreach($movements as $mov)
                        <div class="flex justify-between items-center bg-gray-50 border border-gray-300 rounded p-2">
                            <span class="text-sm">
                                <span class="px-2 py-1 rounded text-xs {{ $mov->type === 'ingreso' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                    {{ ucfirst($mov->type) }}
                                </span>
                                <span class="ml-2">{{ $mov->description ?? 'Sin descripción' }}</span>
                            </span>
                            <span class="text-sm text-gray-600">{{ \Illuminate\Support\Carbon::parse($mov->created_at)->format('H:i:s') }}</span>
                            <span class="font-bold {{ $mov->type === 'ingreso' ? 'text-green-700' : 'text-red-700' }}">
                                {{ $mov->type === 'ingreso' ? '+' : '-' }}{!! formatCurrency($mov->amount) !!}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 bg-yellow-100 p-3 rounded font-bold">
                    <div class="flex justify-between">
                        <span class="text-green-700">Total Ingresos: {!! formatCurrency($ingresos) !!}</span>
                        <span class="text-red-700">Total Egresos: {!! formatCurrency($egresos) !!}</span>
                    </div>
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No hay movimientos adicionales registrados.</p>
            @endif
        </div>

        <!-- PRODUCTOS VENDIDOS (RESUMEN) -->
        <div class="mb-6 border-2 border-gray-300 rounded-lg p-4">
            <h3 class="text-xl font-bold mb-3 bg-indigo-600 text-white p-2 rounded">🧾 PRODUCTOS VENDIDOS (RESUMEN)</h3>
            @if(!empty($soldSummary))
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Producto</th>
                                <th class="p-2 text-right">Cantidad</th>
                                <th class="p-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($soldSummary as $desc => $row)
                                <tr class="border-t">
                                    <td class="p-2">{{ $desc }}</td>
                                    <td class="p-2 text-right">{{ $row['qty'] }}</td>
                                    <td class="p-2 text-right">{!! formatCurrency($row['total']) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600 text-center py-4">No hay productos vendidos hoy.</p>
            @endif
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-3 mt-6 print:hidden">
            <button onclick="window.print()" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-blue-700">
                🖨️ Imprimir / Capturar
            </button>
            <form method="POST" action="{{ route('cash.close') }}" class="flex-1" onsubmit="return confirm('¿Confirmar cierre de caja?')">
                @csrf
                <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-green-700">
                    ✅ Confirmar Cierre
                </button>
            </form>
            <a href="{{ route('pos.index') }}" class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-gray-700 text-center">
                ❌ Cancelar
            </a>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .max-w-6xl, .max-w-6xl * {
        visibility: visible;
    }
    .max-w-6xl {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:border-0 {
        border: 0 !important;
    }
}
</style>
@endsection
