@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Historial de Reparaciones</h1>

<div class="mb-3">
  <a href="{{ route('repairs.index') }}" class="text-blue-600">&larr; Volver a reparaciones</a>
</div>

<!-- Resumen de totales -->
<div class="grid grid-cols-4 gap-4 mb-4">
  <div class="bg-white border rounded p-3">
    <div class="text-xs text-gray-600">Total reparaciones</div>
    <div class="text-2xl font-bold">{{ $totals['repairs_count'] }}</div>
  </div>
  <div class="bg-white border rounded p-3">
    <div class="text-xs text-gray-600">Total cobrado</div>
    <div class="text-2xl font-bold text-green-600">${{ number_format($totals['total_amount'], 2) }}</div>
  </div>
  <div class="bg-white border rounded p-3">
    <div class="text-xs text-gray-600">Total repuestos</div>
    <div class="text-2xl font-bold text-blue-600">${{ number_format($totals['total_parts'], 2) }}</div>
  </div>
  <div class="bg-white border rounded p-3">
    <div class="text-xs text-gray-600">Mano de obra</div>
    <div class="text-2xl font-bold text-purple-600">${{ number_format($totals['total_labor'], 2) }}</div>
  </div>
</div>

<!-- Filtros -->
<div class="bg-white border rounded p-4 mb-4">
  <h2 class="font-semibold mb-3">Filtros</h2>
  <form method="GET" class="grid grid-cols-5 gap-3">
    @if(auth()->user()->role === 'admin')
    <div>
      <label class="block text-xs text-gray-600 mb-1">Técnico</label>
      <select name="technician_id" class="border rounded px-2 py-1 w-full text-sm">
        <option value="">Todos</option>
        @foreach($technicians as $tech)
          <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
            {{ $tech->name }}
          </option>
        @endforeach
      </select>
    </div>
    @endif

    <div>
      <label class="block text-xs text-gray-600 mb-1">Estado</label>
      <select name="status" class="border rounded px-2 py-1 w-full text-sm">
        <option value="">Todos</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completada</option>
        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Entregada</option>
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600 mb-1">Desde</label>
      <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-2 py-1 w-full text-sm">
    </div>

    <div>
      <label class="block text-xs text-gray-600 mb-1">Hasta</label>
      <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-2 py-1 w-full text-sm">
    </div>

    <div>
      <label class="block text-xs text-gray-600 mb-1">Garantía</label>
      <select name="is_warranty" class="border rounded px-2 py-1 w-full text-sm">
        <option value="">Todas</option>
        <option value="1" {{ request('is_warranty') === '1' ? 'selected' : '' }}>Sí</option>
        <option value="0" {{ request('is_warranty') === '0' ? 'selected' : '' }}>No</option>
      </select>
    </div>

    <div class="col-span-5 flex gap-2">
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Aplicar filtros</button>
      <a href="{{ route('repairs.history') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">Limpiar</a>
    </div>
  </form>
</div>

<!-- Tabla de historial -->
<div class="bg-white border rounded overflow-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100 border-b">
      <tr>
        <th class="p-2 text-left">#</th>
        <th class="p-2 text-left">Fecha</th>
        <th class="p-2 text-left">Cliente</th>
        <th class="p-2 text-left">Dispositivo</th>
        <th class="p-2 text-left">Técnico</th>
        <th class="p-2 text-left">Descripción</th>
        <th class="p-2 text-right">Repuestos</th>
        <th class="p-2 text-right">Mano de obra</th>
        <th class="p-2 text-right">Total</th>
        <th class="p-2 text-center">Estado</th>
        <th class="p-2 text-center">Garantía</th>
      </tr>
    </thead>
    <tbody>
      @forelse($repairs as $repair)
      <tr class="border-b hover:bg-gray-50 {{ $repair->is_warranty ? 'bg-orange-50' : '' }}">
        <td class="p-2">{{ $repair->id }}</td>
        <td class="p-2">
          <div>{{ $repair->created_at->format('d/m/Y') }}</div>
          <div class="text-xs text-gray-500">{{ $repair->created_at->format('H:i') }}</div>
        </td>
        <td class="p-2">
          <div class="font-semibold">{{ $repair->customer_name }}</div>
          <div class="text-xs text-gray-600">{{ $repair->customer_phone }}</div>
        </td>
        <td class="p-2">
          <div>{{ $repair->device_description }}</div>
          <div class="text-xs text-gray-600">{{ Str::limit($repair->issue_description, 40) }}</div>
        </td>
        <td class="p-2">
          <div class="font-semibold">{{ $repair->technician->name ?? '-' }}</div>
        </td>
        <td class="p-2">
          <div class="text-xs">{{ $repair->repair_description ?? '-' }}</div>
        </td>
        <td class="p-2 text-right">
          @if($repair->parts_cost)
            <span class="text-blue-600 font-semibold">${{ number_format($repair->parts_cost, 2) }}</span>
          @else
            <span class="text-gray-400">-</span>
          @endif
        </td>
        <td class="p-2 text-right">
          @php($labor = $repair->total_cost - ($repair->parts_cost ?? 0))
          <span class="text-purple-600 font-semibold">${{ number_format($labor, 2) }}</span>
        </td>
        <td class="p-2 text-right">
          <span class="font-bold text-green-600">${{ number_format($repair->total_cost, 2) }}</span>
        </td>
        <td class="p-2 text-center">
          <span class="text-xs px-2 py-1 rounded
            {{ $repair->status==='completed'?'bg-green-100 text-green-800':'' }}
            {{ $repair->status==='delivered'?'bg-gray-100 text-gray-800':'' }}
          ">
            {{ $repair->status === 'completed' ? 'Completada' : 'Entregada' }}
          </span>
        </td>
        <td class="p-2 text-center">
          @if($repair->is_warranty)
            <span class="text-xs px-2 py-1 rounded bg-orange-500 text-white">🔧 SÍ</span>
          @else
            <span class="text-xs text-gray-400">No</span>
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="11" class="p-8 text-center text-gray-500">
          No hay reparaciones que coincidan con los filtros seleccionados
        </td>
      </tr>
      @endforelse
    </tbody>
    @if($repairs->count() > 0)
    <tfoot class="bg-gray-50 border-t-2 font-bold">
      <tr>
        <td colspan="6" class="p-2 text-right">TOTALES (página actual):</td>
        <td class="p-2 text-right text-blue-600">
          ${{ number_format($repairs->sum('parts_cost'), 2) }}
        </td>
        <td class="p-2 text-right text-purple-600">
          ${{ number_format($repairs->sum('total_cost') - $repairs->sum('parts_cost'), 2) }}
        </td>
        <td class="p-2 text-right text-green-600">
          ${{ number_format($repairs->sum('total_cost'), 2) }}
        </td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
    @endif
  </table>
</div>

<div class="mt-4">
  {{ $repairs->links() }}
</div>

@if(auth()->user()->role === 'admin')
<div class="mt-4 bg-blue-50 border border-blue-200 rounded p-4">
  <h3 class="font-semibold text-blue-900 mb-2">💡 Información para pagos</h3>
  <ul class="text-sm text-blue-800 space-y-1">
    <li>• <strong>Repuestos:</strong> Costo de partes/componentes utilizados (descontable del pago al técnico)</li>
    <li>• <strong>Mano de obra:</strong> Total - Repuestos = Monto a pagar al técnico</li>
    <li>• <strong>Garantías:</strong> Las reparaciones marcadas como garantía pueden tener consideración especial</li>
  </ul>
</div>
@endif

@endsection
