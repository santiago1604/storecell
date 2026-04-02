@extends('layouts.app')
@section('content')
@php
    function formatCurrency($amount) {
        return '$ ' . number_format($amount, 0, ',', '.');
    }
@endphp

<div class="mb-6">
  <h1 class="text-2xl font-bold text-gray-900 mb-1">Dashboard de Ventas</h1>
  <p class="text-gray-600 text-sm">Análisis y seguimiento de operaciones diarias</p>
</div>

<!-- Top Action Bar -->
<div class="bg-white border-b border-gray-200 p-4 mb-6 rounded-lg shadow-sm">
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
    <a href="{{ route('dashboard', ['period' => 'today']) }}" class="px-4 py-2 rounded text-sm font-medium transition {{ $period === 'today' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
      Hoy
    </a>
    <a href="{{ route('dashboard', ['period' => 'week']) }}" class="px-4 py-2 rounded text-sm font-medium transition {{ $period === 'week' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
      Esta Semana
    </a>
    <a href="{{ route('dashboard', ['period' => 'month']) }}" class="px-4 py-2 rounded text-sm font-medium transition {{ $period === 'month' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
      Este Mes
    </a>
    <a href="{{ route('dashboard', ['period' => 'custom']) }}" class="px-4 py-2 rounded text-sm font-medium transition {{ $period === 'custom' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
      Personalizado
    </a>
    <a href="{{ route('dashboard', ['period' => 'specific']) }}" class="px-4 py-2 rounded text-sm font-medium transition {{ $period === 'specific' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-gray-700 hover:bg-slate-200' }}">
      Día Específico
    </a>
  </div>
</div>

<!-- Secondary Action Bar -->
<div class="flex flex-wrap gap-3 mb-6">
  <a href="{{ route('reports.session.csv') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition shadow-sm">📊 Descargar CSV</a>
  <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-semibold hover:bg-purple-700 transition shadow-sm">👥 Usuarios</a>
</div>

<!-- Custom Date Range Filter (Show when custom period selected) -->
@if($period === 'custom')
<div class="mb-6 bg-white border border-slate-200 rounded-lg p-4">
  <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-end gap-3">
    <input type="hidden" name="period" value="custom">
    <div class="flex-1">
      <label class="block text-xs text-gray-600 font-medium mb-1">Desde</label>
      <input type="date" name="from" value="{{ $from ?? '' }}" class="border border-slate-300 rounded px-3 py-2 text-sm w-full" />
    </div>
    <div class="flex-1">
      <label class="block text-xs text-gray-600 font-medium mb-1">Hasta</label>
      <input type="date" name="to" value="{{ $to ?? '' }}" class="border border-slate-300 rounded px-3 py-2 text-sm w-full" />
    </div>
    <button type="submit" class="bg-slate-900 text-white px-6 py-2 rounded text-sm font-semibold hover:bg-slate-800 transition whitespace-nowrap">Aplicar</button>
  </form>
</div>
@endif

<!-- Specific Date Filter (Show when specific period selected) -->
@if($period === 'specific')
<div class="mb-6 bg-white border border-slate-200 rounded-lg p-4">
  <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-end gap-3">
    <input type="hidden" name="period" value="specific">
    <div class="flex-1">
      <label class="block text-xs text-gray-600 font-medium mb-1">Selecciona un día</label>
      <input type="date" name="date" value="{{ $specificDate ?? '' }}" class="border border-slate-300 rounded px-3 py-2 text-sm w-full" />
    </div>
    <button type="submit" class="bg-slate-900 text-white px-6 py-2 rounded text-sm font-semibold hover:bg-slate-800 transition whitespace-nowrap">Consultar</button>
  </form>
</div>
@endif

<!-- Period Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <div class="bg-white border border-slate-200 rounded-lg p-4">
    <div class="text-xs text-gray-500 font-medium mb-1">Total vendido</div>
    <div class="text-2xl font-bold text-gray-900">{!! formatCurrency($summary->total ?? 0) !!}</div>
    <div class="text-xs text-gray-600 mt-1">{{ $summary->n ?? 0 }} ventas</div>
  </div>
  <div class="bg-white border border-slate-200 rounded-lg p-4">
    <div class="text-xs text-gray-500 font-medium mb-1">Efectivo</div>
    <div class="text-2xl font-bold text-gray-900">{!! formatCurrency($summary->cash ?? 0) !!}</div>
  </div>
  <div class="bg-white border border-slate-200 rounded-lg p-4">
    <div class="text-xs text-gray-500 font-medium mb-1">Virtual</div>
    <div class="text-2xl font-bold text-gray-900">{!! formatCurrency($summary->virtual ?? 0) !!}</div>
  </div>
  <div class="bg-white border border-slate-200 rounded-lg p-4">
    <div class="text-xs text-gray-500 font-medium mb-1">Ticket promedio</div>
    <div class="text-2xl font-bold text-gray-900">{!! formatCurrency($ticket) !!}</div>
  </div>
</div>

<h2 class="mt-6 font-bold text-gray-900 mb-3">Top 10 Productos del Mes</h2>
<div class="bg-white border border-slate-200 rounded-lg p-4">
  @if(count($top) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
      @foreach($top as $index => $t)
        <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded">
          <div class="flex-shrink-0 text-lg font-bold text-slate-400 w-6 text-center">
            #{{ $index + 1 }}
          </div>
          <div class="flex-1">
            <div class="font-semibold text-gray-800 text-sm">{{ $t->description }}</div>
            <div class="text-xs text-gray-600">{{ $t->qty }} unidades</div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <p class="text-gray-500 text-center py-4">Sin datos de productos este mes.</p>
  @endif
</div>

<h2 class="mt-6 font-bold text-gray-900 mb-3">Resumen de Caja (Hoy)</h2>
<div class="bg-white border border-slate-200 rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
  @if($session)
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Fecha</div>
      <div class="font-bold text-gray-900">{{ $session->date }}</div>
      <div class="text-xs text-gray-600 mt-1">
        @if($session->close_at) ✓ Cerrada @else Abierta @endif
      </div>
    </div>
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Base de caja</div>
      <div class="font-bold text-gray-900">{!! formatCurrency($session->base_amount ?? 0) !!}</div>
    </div>
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Efectivo</div>
      <div class="font-bold text-gray-900">{!! formatCurrency($sum->cash ?? 0) !!}</div>
    </div>
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Virtual</div>
      <div class="font-bold text-gray-900">{!! formatCurrency($sum->virtual ?? 0) !!}</div>
    </div>
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Total vendido</div>
      <div class="font-bold text-gray-900">{!! formatCurrency($sum->total ?? 0) !!}</div>
    </div>
    <div class="p-3 border border-slate-200 rounded">
      <div class="text-xs text-gray-500 font-medium mb-1">Movimientos</div>
      <div class="font-bold text-sm text-gray-900">
        +{!! formatCurrency($mov->ing ?? 0) !!} / -{!! formatCurrency($mov->egr ?? 0) !!}
      </div>
    </div>
    <div class="col-span-full p-4 bg-slate-50 border border-slate-200 rounded">
      <div class="text-sm text-gray-700 font-medium mb-1">En Caja (Total)</div>
      <div class="text-3xl font-bold text-gray-900">{!! formatCurrency($enCaja ?? 0) !!}</div>
    </div>
  @else
    <div class="col-span-2 text-sm text-gray-600 py-6 text-center">
      <p class="mb-3">Sin sesión de caja abierta hoy</p>
      <a href="{{ route('cash.open') }}" class="inline-block bg-slate-900 text-white px-4 py-2 rounded hover:bg-slate-800 text-sm font-medium">Abrir Caja</a>
    </div>
  @endif
</div>

<h2 class="mt-6 font-bold text-gray-900 mb-3">Ventas Detalladas</h2>
<div class="bg-white border border-slate-200 rounded-lg p-4">
  @if(isset($periodSales) && count($periodSales))
    <!-- Desktop View -->
    <div class="hidden sm:block overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 border-b-2">
          <tr>
            <th class="p-3 text-left font-semibold">Fecha/Hora</th>
            <th class="p-3 text-left font-semibold">Usuario</th>
            <th class="p-3 text-left font-semibold">Productos</th>
            <th class="p-3 text-right font-semibold">Total</th>
            <th class="p-3 text-right font-semibold">Efectivo</th>
            <th class="p-3 text-right font-semibold">Virtual</th>
            <th class="p-3 text-center font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($periodSales as $s)
          <tr class="border-t hover:bg-gray-50">
            <td class="p-3 text-sm">
              <div class="font-semibold">{{ $s->created_at->format('d/m/Y') }}</div>
              <div class="text-xs text-gray-600">{{ $s->created_at->format('H:i:s') }}</div>
            </td>
            <td class="p-3 text-sm">{{ $s->user->name ?? '—' }}</td>
            <td class="p-3 text-sm">
              <ul class="list-disc pl-4 space-y-1">
                @foreach($s->items as $it)
                  <li class="text-xs">
                    <span class="font-semibold">{{ $it->quantity }}x</span>
                    {{ $it->product->description ?? $it->description ?? 'Item' }}
                  </li>
                @endforeach
              </ul>
            </td>
            <td class="p-3 text-right font-bold">{!! formatCurrency($s->total) !!}</td>
            <td class="p-3 text-right text-green-700 font-semibold">{!! formatCurrency($s->payment_cash) !!}</td>
            <td class="p-3 text-right text-purple-700 font-semibold">{!! formatCurrency($s->payment_virtual) !!}</td>
            <td class="p-3 text-center">
              <form method="POST" action="{{ route('pos.sales.destroy', $s->id) }}" onsubmit="return confirm('¿Eliminar venta?')" class="inline">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:text-red-800 font-semibold text-sm">Eliminar</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Mobile View -->
    <div class="sm:hidden space-y-3">
      @foreach($periodSales as $s)
      <div class="border rounded p-3 bg-gray-50">
        <div class="flex justify-between items-start mb-2">
          <div>
            <div class="font-bold text-sm">{{ $s->created_at->format('d/m/Y H:i:s') }}</div>
            <div class="text-xs text-gray-600">{{ $s->user->name ?? '—' }}</div>
          </div>
          <div class="text-right">
            <div class="font-bold text-lg">{!! formatCurrency($s->total) !!}</div>
          </div>
        </div>
        <ul class="list-disc pl-4 text-xs space-y-1 mb-2">
          @foreach($s->items as $it)
            <li>{{ $it->quantity }}x {{ $it->product->description ?? $it->description ?? 'Item' }}</li>
          @endforeach
        </ul>
        <div class="flex justify-between text-xs text-gray-600 border-t pt-2 mb-2">
          <span>💵 Efectivo: <strong class="text-green-700">{!! formatCurrency($s->payment_cash) !!}</strong></span>
          <span>📱 Virtual: <strong class="text-purple-700">{!! formatCurrency($s->payment_virtual) !!}</strong></span>
        </div>
        <form method="POST" action="{{ route('pos.sales.destroy', $s->id) }}" onsubmit="return confirm('¿Eliminar venta?')">
          @csrf @method('DELETE')
          <button class="text-red-600 hover:text-red-800 font-semibold text-xs w-full text-center">Eliminar</button>
        </form>
      </div>
      @endforeach
    </div>
  @else
    <p class="text-gray-500 text-center py-6">📭 Sin ventas en este período</p>
  @endif
</div>

<h2 class="mt-6 font-bold text-gray-900 mb-3">Movimientos de Caja (Hoy)</h2>
<div class="bg-white border border-slate-200 rounded-lg p-4">
  @if(isset($movements) && count($movements))
    <!-- Desktop -->
    <div class="hidden sm:block overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 border-b-2">
          <tr>
            <th class="p-3 text-left font-semibold">Hora</th>
            <th class="p-3 text-left font-semibold">Tipo</th>
            <th class="p-3 text-right font-semibold">Monto</th>
            <th class="p-3 text-left font-semibold">Descripción</th>
          </tr>
        </thead>
        <tbody>
          @foreach($movements as $m)
          <tr class="border-t hover:bg-gray-50">
            <td class="p-3 text-sm">{!! $m->created_at->format('H:i:s') !!}</td>
            <td class="p-3">
              <span class="px-2 py-1 rounded text-xs text-white {{ $m->type==='ingreso' ? 'bg-emerald-600' : 'bg-red-600' }}">
                {{ $m->type === 'ingreso' ? '+ Ingreso' : '- Egreso' }}
              </span>
            </td>
            <td class="p-3 text-right font-bold {{ $m->type==='ingreso' ? 'text-green-700' : 'text-red-700' }}">{!! ($m->type === 'ingreso' ? '+' : '-') . formatCurrency($m->amount) !!}</td>
            <td class="p-3">{{ $m->description }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Mobile View -->
    <div class="sm:hidden space-y-2">
      @foreach($movements as $m)
      <div class="border rounded p-3 bg-gray-50">
        <div class="flex justify-between items-start mb-2">
          <div>
            <span class="px-2 py-0.5 rounded text-xs text-white {{ $m->type==='ingreso' ? 'bg-emerald-600' : 'bg-red-600' }}">{{ ucfirst($m->type) }}</span>
            <div class="text-xs text-gray-500 mt-1">{{ $m->created_at->format('H:i:s') }}</div>
          </div>
          <div class="font-bold {{ $m->type==='ingreso' ? 'text-green-700' : 'text-red-700' }}">{!! ($m->type === 'ingreso' ? '+' : '-') . formatCurrency($m->amount) !!}</div>
        </div>
        <div class="text-sm mb-2">{{ $m->description }}</div>
      </div>
      @endforeach
    </div>
  @else
    <p class="text-gray-500 text-center py-6">📭 Sin movimientos en la caja hoy</p>
  @endif

<h2 class="mt-6 font-bold text-gray-900 mb-3">Productos Vendidos (Hoy)</h2>
<div class="bg-white border border-slate-200 rounded-lg p-4">
  @if(isset($soldProducts) && count($soldProducts))
    <!-- Desktop -->
    <div class="hidden sm:block overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 border-b-2">
          <tr>
            <th class="p-3 text-left font-semibold">Producto</th>
            <th class="p-3 text-center font-semibold">Unidades</th>
            <th class="p-3 text-right font-semibold">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($soldProducts as $prod)
          <tr class="border-t hover:bg-gray-50">
            <td class="p-3">{{ $prod->description }}</td>
            <td class="p-3 text-center">
              <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-bold">{{ $prod->qty }}</span>
            </td>
            <td class="p-3 text-right font-bold">{!! formatCurrency($prod->total) !!}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Mobile View -->
    <div class="sm:hidden space-y-2">
      @foreach($soldProducts as $prod)
      <div class="border rounded p-3 bg-gray-50 flex justify-between items-center">
        <div>
          <div class="font-semibold text-sm">{{ $prod->description }}</div>
          <div class="text-xs text-gray-600">{{ $prod->qty }} unidades</div>
        </div>
        <div class="text-right">
          <div class="font-bold">{!! formatCurrency($prod->total) !!}</div>
        </div>
      </div>
      @endforeach
    </div>
  @else
    <p class="text-gray-500 text-center py-6">📭 Sin productos vendidos hoy</p>
  @endif
</div>

@endsection
