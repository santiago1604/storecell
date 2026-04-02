@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Ventas recientes</h1>

@if(auth()->user()->role === 'admin')
  @php($pendingCount = $salesToday->where('pending_delete', true)->count() + $salesOtherDays->where('pending_delete', true)->count())
  @if($pendingCount > 0)
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-3">
      <strong>⚠️ Atención:</strong> Hay {{ $pendingCount }} {{ $pendingCount === 1 ? 'solicitud' : 'solicitudes' }} de eliminación pendiente{{ $pendingCount === 1 ? '' : 's' }} de aprobación.
    </div>
  @endif
@endif

<div class="mb-3">
  <a href="{{ route('pos.index') }}" class="text-blue-600">&larr; Volver al POS</a>
</div>

<div class="space-y-4">
  <!-- Ventas de HOY -->
  <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-4">
    <h2 class="text-lg font-semibold mb-3 flex items-center gap-2">
      <span class="text-2xl">📅</span>
      <span>Ventas de hoy ({{ $salesToday->count() }})</span>
      <span class="text-sm bg-green-600 text-white px-2 py-1 rounded">{{ now()->format('d/m/Y') }}</span>
    </h2>
    <div class="overflow-auto border rounded bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2">#</th>
            <th class="p-2">Hora</th>
            <th class="p-2">Usuario</th>
            <th class="p-2">Total</th>
            <th class="p-2">Efectivo</th>
            <th class="p-2">Virtual</th>
            <th class="p-2">Productos</th>
            <th class="p-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($salesToday as $sale)
          <tr class="border-t {{ $sale->pending_delete ? 'bg-yellow-50' : '' }}">
            <td class="p-2">{{ $sale->id }}</td>
            <td class="p-2 font-semibold">{{ $sale->created_at->format('H:i') }}</td>
            <td class="p-2">{{ $sale->user->email ?? '-' }}</td>
            <td class="p-2 text-right font-bold">${{ number_format($sale->total,2) }}</td>
            <td class="p-2 text-right">${{ number_format($sale->payment_cash,2) }}</td>
            <td class="p-2 text-right">${{ number_format($sale->payment_virtual,2) }}</td>
            <td class="p-2">
              <ul class="list-disc ml-4">
                @foreach($sale->items as $item)
                  @php($cat = optional($item->product->category ?? null)->name)
                  <li>
                    {{ $item->quantity }} x
                    {{ $item->product->description ?? ($item->description ?? 'Producto #'.$item->product_id) }}
                    @if($cat)
                      <span class="text-xs text-gray-500">— {{ $cat }}</span>
                    @endif
                    ({{ number_format($item->unit_price,2) }})
                  </li>
                @endforeach
              </ul>
            </td>
            <td class="p-2">
              @if($sale->pending_delete)
                <!-- Solicitud pendiente -->
                <div class="text-xs mb-1">
                  <span class="px-2 py-1 rounded bg-yellow-200 text-yellow-800">⏳ Pendiente</span>
                  <div class="text-gray-600 mt-1">Por: {{ $sale->requestedBy->name ?? '-' }}</div>
                </div>
                @if(auth()->user()->role === 'admin')
                  <!-- Admin puede aprobar o rechazar -->
                  <div class="flex gap-1 mt-1">
                    <form method="POST" action="{{ route('pos.sales.destroy', $sale) }}" onsubmit="return confirm('¿Aprobar eliminación de esta venta?')">
                      @csrf @method('DELETE')
                      <button class="text-green-600 text-xs px-2 py-1 border border-green-600 rounded hover:bg-green-50">✓ Aprobar</button>
                    </form>
                    <form method="POST" action="{{ route('pos.sales.cancel-delete', $sale) }}">
                      @csrf
                      <button class="text-red-600 text-xs px-2 py-1 border border-red-600 rounded hover:bg-red-50">✕ Rechazar</button>
                    </form>
                  </div>
                @elseif(auth()->user()->id === $sale->requested_by)
                  <!-- El mismo usuario que solicitó puede cancelar -->
                  <form method="POST" action="{{ route('pos.sales.cancel-delete', $sale) }}">
                    @csrf
                    <button class="text-gray-600 text-xs">Cancelar solicitud</button>
                  </form>
                @endif
              @else
                <!-- No hay solicitud pendiente -->
                @if(auth()->user()->role === 'admin')
                  <form method="POST" action="{{ route('pos.sales.destroy', $sale) }}" onsubmit="return confirm('¿Eliminar esta venta?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Eliminar</button>
                  </form>
                @else
                  <!-- Vendedor/Técnico puede solicitar eliminación -->
                  <form method="POST" action="{{ route('pos.sales.request-delete', $sale) }}" onsubmit="return confirm('¿Solicitar eliminación de esta venta?')">
                    @csrf
                    <button class="text-orange-600 text-xs">Solicitar eliminación</button>
                  </form>
                @endif
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="p-4 text-center text-gray-500">No hay ventas registradas hoy</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ventas de OTROS DÍAS -->
  <div>
    <h2 class="text-lg font-semibold mb-2">Ventas de días anteriores (últimas 50)</h2>
    <div class="overflow-auto border rounded bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2">#</th>
            <th class="p-2">Fecha</th>
            <th class="p-2">Usuario</th>
            <th class="p-2">Total</th>
            <th class="p-2">Efectivo</th>
            <th class="p-2">Virtual</th>
            <th class="p-2">Productos</th>
            <th class="p-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($salesOtherDays as $sale)
          <tr class="border-t {{ $sale->pending_delete ? 'bg-yellow-50' : '' }}">
            <td class="p-2">{{ $sale->id }}</td>
            <td class="p-2">{{ $sale->created_at }}</td>
            <td class="p-2">{{ $sale->user->email ?? '-' }}</td>
            <td class="p-2 text-right font-bold">${{ number_format($sale->total,2) }}</td>
            <td class="p-2 text-right">${{ number_format($sale->payment_cash,2) }}</td>
            <td class="p-2 text-right">${{ number_format($sale->payment_virtual,2) }}</td>
            <td class="p-2">
              <ul class="list-disc ml-4">
                @foreach($sale->items as $item)
                  @php($cat = optional($item->product->category ?? null)->name)
                  <li>
                    {{ $item->quantity }} x
                    {{ $item->product->description ?? ($item->description ?? 'Producto #'.$item->product_id) }}
                    @if($cat)
                      <span class="text-xs text-gray-500">— {{ $cat }}</span>
                    @endif
                    ({{ number_format($item->unit_price,2) }})
                  </li>
                @endforeach
              </ul>
            </td>
            <td class="p-2">
              @if($sale->pending_delete)
                <!-- Solicitud pendiente -->
                <div class="text-xs mb-1">
                  <span class="px-2 py-1 rounded bg-yellow-200 text-yellow-800">⏳ Pendiente</span>
                  <div class="text-gray-600 mt-1">Por: {{ $sale->requestedBy->name ?? '-' }}</div>
                </div>
                @if(auth()->user()->role === 'admin')
                  <!-- Admin puede aprobar o rechazar -->
                  <div class="flex gap-1 mt-1">
                    <form method="POST" action="{{ route('pos.sales.destroy', $sale) }}" onsubmit="return confirm('¿Aprobar eliminación de esta venta?')">
                      @csrf @method('DELETE')
                      <button class="text-green-600 text-xs px-2 py-1 border border-green-600 rounded hover:bg-green-50">✓ Aprobar</button>
                    </form>
                    <form method="POST" action="{{ route('pos.sales.cancel-delete', $sale) }}">
                      @csrf
                      <button class="text-red-600 text-xs px-2 py-1 border border-red-600 rounded hover:bg-red-50">✕ Rechazar</button>
                    </form>
                  </div>
                @elseif(auth()->user()->id === $sale->requested_by)
                  <!-- El mismo usuario que solicitó puede cancelar -->
                  <form method="POST" action="{{ route('pos.sales.cancel-delete', $sale) }}">
                    @csrf
                    <button class="text-gray-600 text-xs">Cancelar solicitud</button>
                  </form>
                @endif
              @else
                <!-- No hay solicitud pendiente -->
                @if(auth()->user()->role === 'admin')
                  <form method="POST" action="{{ route('pos.sales.destroy', $sale) }}" onsubmit="return confirm('¿Eliminar esta venta?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Eliminar</button>
                  </form>
                @else
                  <!-- Vendedor/Técnico puede solicitar eliminación -->
                  <form method="POST" action="{{ route('pos.sales.request-delete', $sale) }}" onsubmit="return confirm('¿Solicitar eliminación de esta venta?')">
                    @csrf
                    <button class="text-orange-600 text-xs">Solicitar eliminación</button>
                  </form>
                @endif
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="p-4 text-center text-gray-500">No hay ventas de días anteriores</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Reparaciones entregadas -->
  <div>
    <h2 class="text-lg font-semibold mb-2">Reparaciones entregadas</h2>
    <div class="overflow-auto border rounded bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2">#</th>
            <th class="p-2">Fecha entrega</th>
            <th class="p-2">Cliente</th>
            <th class="p-2">Dispositivo</th>
            <th class="p-2">Total</th>
            <th class="p-2">Recibió</th>
            <th class="p-2">Estado</th>
            <th class="p-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($repairs as $repair)
          <tr class="border-t">
            <td class="p-2">{{ $repair->id }}</td>
            <td class="p-2">{{ $repair->delivered_at ? $repair->delivered_at->format('d/m/Y H:i') : '-' }}</td>
            <td class="p-2">
              <div class="font-semibold">{{ $repair->customer_name }}</div>
              <div class="text-xs text-gray-600">{{ $repair->customer_phone }}</div>
            </td>
            <td class="p-2">
              <div>{{ $repair->device_description }}</div>
              @if($repair->repair_description)
                <div class="text-xs text-gray-600">{{ Str::limit($repair->repair_description, 50) }}</div>
              @endif
            </td>
            <td class="p-2 text-right font-bold">${{ number_format($repair->total_cost,2) }}</td>
            <td class="p-2">{{ $repair->receivedBy->name ?? '-' }}</td>
            <td class="p-2">
              <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-800">Entregado</span>
            </td>
            <td class="p-2">
              @if(auth()->user()->role === 'admin')
                <button onclick="showWarrantyModal({{ $repair->id }}, '{{ $repair->customer_name }}', '{{ $repair->device_description }}')" 
                  class="text-orange-600 hover:text-orange-800 text-xs">
                  Garantía
                </button>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="p-4 text-center text-gray-500">No hay reparaciones entregadas</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal de garantía -->
<div id="warrantyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
    <h3 class="text-lg font-bold mb-4">Marcar reparación como garantía</h3>
    <p class="text-sm text-gray-600 mb-3">
      Cliente: <span id="modalCustomerName" class="font-semibold"></span><br>
      Dispositivo: <span id="modalDeviceDescription" class="font-semibold"></span>
    </p>
    <form id="warrantyForm" method="POST">
      @csrf
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Notas de garantía (opcional)</label>
        <textarea name="warranty_notes" rows="3" class="border rounded px-3 py-2 w-full" 
          placeholder="Ej: Volvió por el mismo problema, pantalla se apaga..."></textarea>
      </div>
      <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4 text-sm">
        <strong>⚠️ Atención:</strong> Esta acción:
        <ul class="list-disc ml-5 mt-1">
          <li>Eliminará los movimientos de caja de esta entrega</li>
          <li>Regresará la reparación a estado "En proceso"</li>
          <li>Marcará la reparación como garantía</li>
        </ul>
      </div>
      <div class="flex gap-2 justify-end">
        <button type="button" onclick="closeWarrantyModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded">
          Cancelar
        </button>
        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded">
          Confirmar garantía
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function showWarrantyModal(repairId, customerName, deviceDescription) {
    document.getElementById('warrantyModal').classList.remove('hidden');
    document.getElementById('modalCustomerName').textContent = customerName;
    document.getElementById('modalDeviceDescription').textContent = deviceDescription;
    document.getElementById('warrantyForm').action = '/repairs/' + repairId + '/warranty';
  }

  function closeWarrantyModal() {
    document.getElementById('warrantyModal').classList.add('hidden');
  }

  // Cerrar modal al hacer clic fuera
  document.getElementById('warrantyModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeWarrantyModal();
    }
  });
</script>
@endsection
