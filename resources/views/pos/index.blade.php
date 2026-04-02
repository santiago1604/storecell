@extends('layouts.app')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-4">
  <div class="lg:col-span-8 order-2 lg:order-1">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 gap-2">
      <h1 class="text-lg sm:text-xl font-bold">Punto de Venta</h1>
      <div class="flex gap-2">
        <a href="{{ route('pos.sales') }}" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs sm:text-sm whitespace-nowrap hover:bg-blue-700">Ver ventas</a>
        <a href="{{ route('repairs.index') }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-xs sm:text-sm whitespace-nowrap hover:bg-indigo-700">Reparaciones</a>
      </div>
    </div>
    @if(session('ok'))
      <div class="mb-3 p-2 sm:p-3 rounded border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
      <div class="mb-3 p-2 sm:p-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm">{{ session('err') }}</div>
    @endif
    <div class="mb-3">
      <a href="{{ route('reports.session.csv') }}" class="inline-block bg-gray-700 text-white px-3 py-1.5 rounded text-xs sm:text-sm hover:bg-gray-800">📥 Descargar CSV (sesión)</a>
    </div>

    <form method="POST" action="{{ route('pos.addItem') }}" class="flex flex-col sm:flex-row gap-2 mb-3" id="addItemForm">
      @csrf
      <div class="relative flex-1 min-w-0">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd" />
          </svg>
        </div>
        <input type="text" id="posSearch" placeholder="Buscar producto..." class="border pl-10 p-2 w-full rounded text-base" autocomplete="off" />
        <ul id="searchResults" class="absolute z-20 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-auto hidden"></ul>
      </div>
      <input type="hidden" name="product_id" id="productId" required />
      <div id="selectedInfo" class="text-xs sm:text-sm text-gray-600 hidden sm:block"></div>
      <div class="flex gap-2 w-full sm:w-auto">
        <input type="number" name="quantity" min="1" value="1" class="border p-2 w-20 sm:w-24 rounded text-base" />
        <button class="bg-black text-white px-4 sm:px-6 rounded hover:bg-gray-800 whitespace-nowrap">Agregar</button>
      </div>
    </form>

    <form method="POST" action="{{ route('pos.addRecharge') }}" class="flex flex-col sm:flex-row gap-2 mb-3 border rounded p-3 bg-white">
      @csrf
      <input type="text" name="recharge_description" placeholder="Descripción de recarga (ej. Recarga Claro 30000)" class="border p-2 flex-1 rounded text-base" required />
      <input type="text" inputmode="decimal" name="recharge_amount" placeholder="$ 0" class="border p-2 w-full sm:w-40 rounded text-base currency-input" required />
      <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 whitespace-nowrap">Agregar recarga</button>
    </form>

    <!-- Vista tabla desktop -->
    <div class="hidden sm:block overflow-auto border rounded bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 text-left">Descripción</th>
            <th class="p-2 text-right">Cant.</th>
            <th class="p-2 text-right">Precio</th>
            <th class="p-2 text-right">Subtotal</th>
            <th class="p-2 text-right">Acción</th>
          </tr>
        </thead>
        <tbody>
        @php $total = 0; @endphp
        @foreach($items as $idx => $it)
          @php $total += $it['subtotal']; @endphp
          <tr class="border-t">
            <td class="p-2">{{ $it['description'] }}</td>
            <td class="p-2 text-right">{{ $it['qty'] }}</td>
            <td class="p-2 text-right">{{ number_format($it['unit_price'],2) }}</td>
            <td class="p-2 text-right font-semibold">{{ number_format($it['subtotal'],2) }}</td>
            <td class="p-2 text-right">
              <form method="POST" action="{{ route('pos.removeItem') }}">
                @csrf
                <input type="hidden" name="index" value="{{ $idx }}" />
                <button class="text-red-600 hover:underline">Eliminar</button>
              </form>
            </td>
          </tr>
        @endforeach
        </tbody>
        <tfoot>
          <tr class="bg-gray-50">
            <td class="p-2 font-bold" colspan="3">TOTAL</td>
            <td class="p-2 text-right font-bold" colspan="2">${{ number_format($total,2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
    
    <!-- Vista tarjetas móvil -->
    <div class="sm:hidden space-y-2 mb-3">
      @php $total = 0; @endphp
      @foreach($items as $idx => $it)
        @php $total += $it['subtotal']; @endphp
        <div class="bg-white border rounded p-3">
          <div class="flex justify-between items-start mb-2">
            <div class="flex-1">
              <h4 class="font-semibold text-sm">{{ $it['description'] }}</h4>
              <div class="text-xs text-gray-500">Cant: {{ $it['qty'] }} × ${{ number_format($it['unit_price'],2) }}</div>
            </div>
            <div class="font-bold">${{ number_format($it['subtotal'],2) }}</div>
          </div>
          <form method="POST" action="{{ route('pos.removeItem') }}">
            @csrf
            <input type="hidden" name="index" value="{{ $idx }}" />
            <button class="w-full bg-red-600 text-white px-3 py-1.5 rounded text-sm hover:bg-red-700">Eliminar</button>
          </form>
        </div>
      @endforeach
      <div class="bg-gray-100 border rounded p-3 font-bold flex justify-between text-lg">
        <span>TOTAL</span>
        <span>${{ number_format($total,2) }}</span>
      </div>
    </div>

    @php $orderTotalHelper = 0; foreach($items as $it){ $orderTotalHelper += $it['subtotal']; } @endphp
    <form method="POST" action="{{ route('pos.checkout') }}" class="mt-3 border rounded p-3 sm:p-4 bg-white" id="checkoutForm" data-total="{{ $orderTotalHelper }}">
      @csrf
      <div class="flex flex-col gap-3">
        <label class="font-semibold text-sm sm:text-base">Tipo de pago:</label>
        <select name="pay_type" class="border p-2 rounded text-base w-full sm:w-auto" id="payType">
          <option value="cash">Efectivo</option>
          <option value="virtual">Virtual</option>
          <option value="mixed">Mixto</option>
        </select>
        <div class="flex items-center gap-2">
          <button type="button" id="openGatewayBtn" class="text-xs sm:text-sm px-3 py-1.5 rounded border border-indigo-300 text-indigo-700 bg-indigo-50 hover:bg-indigo-100">Pago Bold / Sistecrédito</button>
          <span id="gatewayBadge" class="hidden text-[11px] px-2 py-1 rounded bg-indigo-600 text-white">Método: <span id="gatewayBadgeText"></span></span>
        </div>
        <input type="text" inputmode="decimal" name="payment_cash" id="paymentCash" placeholder="$ 0" class="border p-2 rounded text-base w-full hidden currency-input" />
        <input type="text" inputmode="decimal" name="payment_virtual" id="paymentVirtual" placeholder="$ 0" class="border p-2 rounded text-base w-full hidden currency-input" />
        <div id="checkoutHelperBox" class="mt-2 text-xs sm:text-sm bg-blue-50 border border-blue-200 rounded p-2 hidden">
          <div class="flex justify-between"><span>Total a pagar:</span><span id="helperTotal" class="font-semibold"></span></div>
          <div class="flex justify-between"><span>Pagado:</span><span id="helperPaid" class="font-semibold"></span></div>
          <div class="flex justify-between"><span id="helperLabel">Cambio:</span><span id="helperChange" class="font-bold"></span></div>
        </div>
        <button class="bg-emerald-600 text-white px-6 py-3 rounded font-semibold hover:bg-emerald-700 text-base sm:text-lg">💳 Cobrar</button>
      </div>

      <!-- Drawer Pago Bold / Sistecrédito -->
      <div id="gatewayDrawer" class="fixed inset-0 z-40 hidden">
        <div id="gatewayOverlay" class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-y-0 right-0 w-full sm:w-[440px] bg-white shadow-xl border-l transform transition-transform duration-200 ease-out translate-x-full" id="gatewayPanel">
          <div class="flex items-center justify-between p-3 border-b">
            <div class="font-semibold">Pago con Bold / Sistecrédito</div>
            <button type="button" id="closeGatewayBtn" class="text-gray-600 hover:text-black text-xl" title="Cerrar">×</button>
          </div>
          <div class="p-3 space-y-3 text-sm">
            <div>
              <div class="text-xs text-gray-600 mb-1">Selecciona el sistema</div>
              <label class="mr-4"><input type="radio" name="gateway_type" value="bold" class="mr-1">Bold</label>
              <label><input type="radio" name="gateway_type" value="sistecredito" class="mr-1">Sistecrédito</label>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Comisión: 5% fijo</label>
              <div class="bg-indigo-50 border border-indigo-200 rounded p-2">
                <div class="text-center mb-2">
                  <div class="text-xs text-gray-600">Valor de la comisión</div>
                  <div id="gwCommissionAmount" class="text-lg font-bold text-indigo-700">$ 0,00</div>
                </div>
                <div>
                  <label class="block text-xs text-gray-600 mb-1">¿Cómo se recibe la comisión?</label>
                  <select name="gateway_fee_payment_method" id="gatewayFeeMethod" class="border rounded px-2 py-1 w-full text-xs">
                    <option value="virtual">Virtual</option>
                    <option value="cash">Efectivo</option>
                  </select>
                </div>
              </div>
            </div>
            <div>
              <div class="text-xs text-gray-600 mb-1">Productos a financiar</div>
              <div class="max-h-44 overflow-auto border rounded p-2 space-y-1">
                @foreach($items as $idx => $it)
                  <label class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex items-center gap-2">
                      <input type="checkbox" name="gateway_item_indexes[]" value="{{ $idx }}" class="gateway-item" data-subtotal="{{ $it['subtotal'] }}" checked>
                      <span class="truncate max-w-[210px]">{{ $it['description'] }} × {{ $it['qty'] }}</span>
                    </span>
                    <span class="whitespace-nowrap">${{ number_format($it['subtotal'],2) }}</span>
                  </label>
                @endforeach
              </div>
              <div class="mt-2 bg-gray-50 border rounded p-2 text-xs">
                <div class="flex justify-between"><span>Subtotal seleccionado:</span><span id="gwSubtotal" class="font-semibold">$ 0,00</span></div>
                <div class="flex justify-between"><span>Total a recibir:</span><span id="gwNet" class="font-bold">$ 0,00</span></div>
              </div>
              <div class="text-[11px] text-gray-500 mt-1">Nota: el pago principal llega completo por virtual. Solo cobra la comisión (5%) según el método seleccionado.</div>
            </div>
          </div>
          <div class="p-3 border-t flex justify-end gap-2">
            <button type="button" id="applyGatewayBtn" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Aplicar</button>
            <button type="button" id="cancelGatewayBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="lg:col-span-4 order-1 lg:order-2">
    <div class="lg:sticky lg:top-4 space-y-3">
      <div class="border rounded-lg shadow-sm bg-gradient-to-br from-white to-gray-50 overflow-hidden">
        <!-- Header (clic para desplegar/ocultar) -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-3 sm:p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs sm:text-sm opacity-90">Sesión de caja</div>
              <div class="text-lg sm:text-xl font-bold">{{ optional($session)->date }}</div>
            </div>
            <button type="button" id="cashDetailsToggle" class="text-white/90 hover:text-white text-xl leading-none select-none" aria-expanded="true" aria-controls="cashDetailsContent" title="Mostrar/ocultar detalles">▾</button>
          </div>
        </div>

        <div id="cashDetailsContent" class="p-3 sm:p-4 space-y-3">
          <!-- Caja inicial -->
          <div class="flex justify-between items-center pb-2 border-b border-gray-200">
            <span class="text-xs sm:text-sm text-gray-600">💰 Caja inicial:</span>
            <span class="font-semibold text-sm sm:text-base">${{ number_format($summary['base'] ?? 0,2) }}</span>
          </div>

          <!-- Ingresos (solo tres totales) -->
          <div class="bg-green-50 rounded-lg p-3 sm:p-4 space-y-3">
            <div class="text-xs font-semibold text-green-800 flex items-center gap-1">📈 INGRESOS DEL DÍA</div>
            <div class="space-y-1">
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-gray-700">Ventas POS</span>
                <span class="font-semibold text-green-700">${{ number_format($summary['ventas_pos'] ?? 0,2) }}</span>
              </div>
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-gray-700">Servicio Técnico</span>
                <span class="font-semibold text-purple-700">${{ number_format($summary['total_st'] ?? 0,2) }}</span>
              </div>
              <div class="border-t border-green-200 pt-2 mt-2 flex justify-between items-center text-sm sm:text-base">
                <span class="font-semibold text-gray-800">Total: </span>
                <span class="font-bold text-gray-900">${{ number_format($summary['total_ventas'] ?? 0,2) }}</span>
              </div>
            </div>
            <div class="text-[10px] sm:text-xs text-gray-500 italic">Las recargas ya están incluidas dentro de Ventas POS.</div>
          </div>

          <!-- Egresos -->
          <div class="bg-red-50 rounded-lg p-3 sm:p-4 space-y-3">
            <div class="text-xs font-semibold text-red-800 flex items-center gap-1">📉 EGRESOS DEL DÍA</div>
            <div class="space-y-1">
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-gray-700">Tienda</span>
                <span class="font-semibold text-red-700">-${{ number_format($summary['egresos_tienda'] ?? 0,2) }}</span>
              </div>
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-gray-700">Servicio Técnico</span>
                <span class="font-semibold text-red-700">-${{ number_format($summary['egresos_st'] ?? 0,2) }}</span>
              </div>
              <div class="border-t border-red-200 pt-2 mt-2 flex justify-between items-center text-sm sm:text-base">
                <span class="font-semibold text-gray-800">Total Egresos Día</span>
                <span class="font-bold text-red-800">-${{ number_format(($summary['egresos_tienda'] ?? 0) + ($summary['egresos_st'] ?? 0),2) }}</span>
              </div>
            </div>
          </div>

          <!-- Totales finales -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 sm:p-4 space-y-2 border-2 border-blue-200">
            <div class="text-xs font-semibold text-blue-900 mb-2">💵 TOTALES DEL DÍA</div>
            <div class="grid grid-cols-2 gap-2 text-sm sm:text-base">
              <div class="font-semibold text-green-700">Total Efectivo:</div>
              <div class="text-right font-bold text-green-700">${{ number_format($summary['efectivo'] ?? 0,2) }}</div>
              <div class="font-semibold text-blue-700">Total Virtual:</div>
              <div class="text-right font-bold text-blue-700">${{ number_format($summary['virtual'] ?? 0,2) }}</div>
              <div class="font-semibold text-purple-700">Total Recargas:</div>
              <div class="text-right font-bold text-purple-700">${{ number_format($summary['recargas'] ?? 0,2) }}</div>
              <div class="col-span-2 border-t-2 border-blue-300 pt-2 mt-1"></div>
              <div class="font-bold text-gray-900 text-base sm:text-lg">TOTAL VENTAS:</div>
              <div class="text-right font-bold text-gray-900 text-lg sm:text-xl">${{ number_format($summary['total_ventas'] ?? 0,2) }}</div>
            </div>
          </div>
        </div>
      </div>
      @if($session)
      <a href="{{ route('cash.close.summary') }}" class="block">
        <button class="bg-red-600 text-white px-4 py-2.5 rounded w-full hover:bg-red-700 font-medium">🔒 Cerrar caja</button>
      </a>
      <div class="border rounded p-3 sm:p-4 bg-white">
        <div class="text-sm font-semibold mb-3">Movimiento de caja</div>
        <form method="POST" action="{{ route('cash.movement.add') }}" class="space-y-2" id="movementForm">
          @csrf
          <div>
            <label class="text-xs font-medium text-gray-700 block mb-1">Tipo</label>
            <select name="type" id="movementType" class="border p-2 w-full rounded text-base">
              <option value="egreso">Egreso (sale dinero)</option>
              <option value="ingreso">Ingreso (entra dinero)</option>
            </select>
          </div>
          <div id="paymentMethodField" class="hidden">
            <label class="text-xs font-medium text-gray-700 block mb-1">Método (efectivo o virtual)</label>
            <select name="payment_method" class="border p-2 w-full rounded text-base">
              <option value="cash">Efectivo</option>
              <option value="virtual">Virtual</option>
            </select>
          </div>
          <div id="egresoTypeField" class="hidden">
            <label class="text-xs font-medium text-gray-700 block mb-1">Categoría del egreso</label>
            <select name="egreso_type" class="border p-2 w-full rounded text-base">
              <option value="tienda">Tienda</option>
              <option value="st">Servicio Técnico (ST)</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-700 block mb-1">Descripción</label>
            <input type="text" name="description" class="border p-2 w-full rounded text-base" placeholder="p. ej. Limpieza, pedido..." />
          </div>
          <div>
            <label class="text-xs font-medium text-gray-700 block mb-1">Monto</label>
            <input type="text" inputmode="decimal" name="amount" class="border p-2 w-full rounded text-base currency-input" placeholder="$ 0" required />
          </div>
          <button class="bg-gray-800 text-white px-3 py-2 rounded w-full hover:bg-gray-900">Guardar movimiento</button>
        </form>
      </div>
      @if(isset($movements) && $movements->count())
      <div class="border rounded p-3 sm:p-4 bg-white">
        <div class="text-sm font-semibold mb-2">Últimos movimientos ({{ $movements->count() }})</div>
        <ul class="text-xs sm:text-sm space-y-1.5 max-h-72 overflow-y-auto border border-gray-200 rounded p-2 bg-gray-50">
          @foreach($movements as $m)
            <li class="flex justify-between gap-2 items-center py-1">
              <span class="truncate">
                @php
                  $badge = 'bg-red-600';
                  if ($m->type === 'ingreso') $badge = 'bg-emerald-600';
                  elseif ($m->type === 'deposit') $badge = 'bg-emerald-700';
                @endphp
                <span class="px-1.5 py-0.5 rounded text-white text-xs {{ $badge }}">{{ $m->type === 'deposit' ? 'Depósito' : ucfirst($m->type) }}</span>
                <span class="ml-1">{{ $m->description ?? '-' }}</span>
              </span>
              <span class="whitespace-nowrap flex items-center gap-2">
                ${{ number_format($m->amount,2) }}
                <form method="POST" action="{{ route('cash.movement.delete', $m->id) }}" onsubmit="return confirm('¿Eliminar movimiento?')">
                  @csrf @method('DELETE')
                  <button title="Eliminar" class="text-red-600 font-bold">✕</button>
                </form>
              </span>
            </li>
          @endforeach
        </ul>
      </div>
      @endif
      @else
      <div class="border rounded p-3 sm:p-4 bg-yellow-50">
        <div class="font-semibold text-sm sm:text-base">No hay caja abierta</div>
        <div class="text-xs sm:text-sm mt-1">Pídele al administrador que fije la base de hoy.</div>
      </div>
      @endif
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('posSearch');
  const list = document.getElementById('searchResults');
  const productId = document.getElementById('productId');
  const selectedInfo = document.getElementById('selectedInfo');
  let timer;

  function hideList() { list.classList.add('hidden'); }
  function showList() { list.classList.remove('hidden'); }

  function render(items) {
    list.innerHTML = '';
    if (!items.length) { hideList(); return; }
    items.forEach(it => {
      const li = document.createElement('li');
      li.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer flex justify-between gap-2';
      const left = document.createElement('div');
      left.innerHTML = `<div class="font-medium">${it.text}</div><div class="text-xs text-gray-500">${it.category || ''}${it.barcode ? ' • ' + it.barcode : ''}</div>`;
      const right = document.createElement('div');
      right.innerHTML = `<div class="text-sm text-gray-700">$${it.price.toFixed(2)}</div><div class="text-xs ${it.stock>0?'text-emerald-600':'text-red-600'}">Stock: ${it.stock}</div>`;
      li.appendChild(left); li.appendChild(right);
      li.addEventListener('click', () => {
        // Guardar selección y permitir ajustar cantidad antes de agregar
        productId.value = it.id;
        selectedInfo.textContent = `${it.text} — $${it.price.toFixed(2)} | Stock: ${it.stock}`;
        hideList();
        input.value = it.text;
      });
      list.appendChild(li);
    });
    showList();
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { hideList(); return; }
    timer = setTimeout(async () => {
      try {
        const url = new URL(`{{ route('pos.search') }}`, window.location.origin);
        url.searchParams.set('q', q);
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Error de red');
        const data = await res.json();
        render(data);
      } catch (e) { console.error(e); }
    }, 250);
  });

  document.addEventListener('click', (e) => {
    if (!list.contains(e.target) && e.target !== input) {
      hideList();
    }
  });

  // Mostrar campos según tipo de pago
  const payType = document.getElementById('payType');
  const cashInput = document.getElementById('paymentCash');
  const virtualInput = document.getElementById('paymentVirtual');
  function updatePayFields() {
    const v = payType.value;
    if (v === 'mixed') {
      cashInput.classList.remove('hidden');
      virtualInput.classList.remove('hidden');
      document.getElementById('checkoutHelperBox').classList.remove('hidden');
    } else if (v === 'cash') {
      cashInput.classList.remove('hidden');
      virtualInput.classList.add('hidden');
      virtualInput.value = '';
      document.getElementById('checkoutHelperBox').classList.remove('hidden');
    } else {
      // virtual
      virtualInput.classList.remove('hidden');
      cashInput.classList.add('hidden');
      cashInput.value = '';
      document.getElementById('checkoutHelperBox').classList.remove('hidden');
    }
    updateCheckoutHelper();
  }
  payType.addEventListener('change', updatePayFields);
  updatePayFields();

  // Validar selección de producto al enviar Agregar
  const addForm = document.getElementById('addItemForm');
  addForm.addEventListener('submit', (e) => {
    if (!productId.value) {
      e.preventDefault();
      alert('Selecciona un producto de la búsqueda.');
    }
  });
  // Actualizar helper cuando cambian montos
  const checkoutForm = document.getElementById('checkoutForm');
  if (checkoutForm) {
    const bindChangeEvents = () => {
      ['input','blur','focus'].forEach(evt => {
        if (cashInput) cashInput.addEventListener(evt, updateCheckoutHelper);
        if (virtualInput) virtualInput.addEventListener(evt, updateCheckoutHelper);
      });
    };
    bindChangeEvents();
    updateCheckoutHelper();
  }

  // Drawer Bold/Sistecrédito
  const drawer = document.getElementById('gatewayDrawer');
  const panel = document.getElementById('gatewayPanel');
  const overlay = document.getElementById('gatewayOverlay');
  const openBtn = document.getElementById('openGatewayBtn');
  const closeBtn = document.getElementById('closeGatewayBtn');
  const cancelBtn = document.getElementById('cancelGatewayBtn');
  const applyBtn = document.getElementById('applyGatewayBtn');
  const feeInput = document.querySelector('input[name="gateway_fee_percent"]');
  const itemChecks = () => Array.from(document.querySelectorAll('input.gateway-item'));
  const subtotalEl = document.getElementById('gwSubtotal');
  const feeEl = document.getElementById('gwFee');
  const netEl = document.getElementById('gwNet');
  const gatewayBadge = document.getElementById('gatewayBadge');
  const gatewayBadgeText = document.getElementById('gatewayBadgeText');
  const gatewayTypeInputs = () => Array.from(document.querySelectorAll('input[name="gateway_type"]'));

  function openGateway() {
    if (!drawer || !panel) return;
    drawer.classList.remove('hidden');
    requestAnimationFrame(() => panel.classList.remove('translate-x-full'));
    updateGatewaySummary();
  }
  function closeGateway() {
    if (!drawer || !panel) return;
    panel.classList.add('translate-x-full');
    setTimeout(() => drawer.classList.add('hidden'), 200);
  }
  function getSelectedGatewayType() {
    const sel = gatewayTypeInputs().find(r => r.checked);
    return sel ? sel.value : '';
  }
  function updateGatewaySummary() {
    const sum = itemChecks().filter(c => c.checked).reduce((acc, c) => acc + Number(c.dataset.subtotal || 0), 0);
    const fee = sum * 0.05; // 5% fijo
    const net = Math.max(0, sum - fee);
    subtotalEl.textContent = formatPlainCurrency(sum);
    netEl.textContent = formatPlainCurrency(net);
    document.getElementById('gwCommissionAmount').textContent = formatPlainCurrency(fee);
  }
  function applyGateway() {
    const type = getSelectedGatewayType();
    if (type) {
      gatewayBadgeText.textContent = type === 'bold' ? 'Bold' : 'Sistecrédito';
      gatewayBadge.classList.remove('hidden');
      // Calcular comisión y total del carrito
      const form = document.getElementById('checkoutForm');
      const cartTotal = Number(form?.dataset?.total || 0);
      const sum = itemChecks().filter(c => c.checked).reduce((acc, c) => acc + Number(c.dataset.subtotal || 0), 0);
      const fee = sum * 0.05;
      const feeMethod = document.getElementById('gatewayFeeMethod')?.value || 'virtual';
      
      // Cambiar a tipo de pago mixto
      const paySel = document.getElementById('payType');
      if (paySel && paySel.value !== 'mixed') {
        paySel.value = 'mixed';
        paySel.dispatchEvent(new Event('change'));
      }
      
      // El pago principal (total carrito) siempre va a virtual
      if (virtualInput) {
        virtualInput.value = cartTotal.toFixed(2);
        virtualInput.dispatchEvent(new Event('blur'));
        virtualInput.dispatchEvent(new Event('input'));
      }
      
      // La comisión va al campo según selección
      if (feeMethod === 'cash' && cashInput) {
        cashInput.value = fee.toFixed(2);
        cashInput.dispatchEvent(new Event('blur'));
        cashInput.dispatchEvent(new Event('input'));
      } else if (feeMethod === 'virtual' && virtualInput) {
        // Sumar la comisión al virtual (ya tiene el total del carrito)
        virtualInput.value = (cartTotal + fee).toFixed(2);
        virtualInput.dispatchEvent(new Event('blur'));
        virtualInput.dispatchEvent(new Event('input'));
      }
    } else {
      gatewayBadge.classList.add('hidden');
    }
    closeGateway();
  }
  if (openBtn) openBtn.addEventListener('click', openGateway);
  if (closeBtn) closeBtn.addEventListener('click', closeGateway);
  if (cancelBtn) cancelBtn.addEventListener('click', closeGateway);
  if (overlay) overlay.addEventListener('click', closeGateway);
  if (applyBtn) applyBtn.addEventListener('click', applyGateway);
  itemChecks().forEach(c => c.addEventListener('change', updateGatewaySummary));

  // Toggle detalles de caja (acordeón)
  const cashToggle = document.getElementById('cashDetailsToggle');
  const cashContent = document.getElementById('cashDetailsContent');
  if (cashToggle && cashContent) {
    const setExpanded = (expanded) => {
      cashToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      cashToggle.textContent = expanded ? '▾' : '▸';
      if (expanded) cashContent.classList.remove('hidden'); else cashContent.classList.add('hidden');
      try { localStorage.setItem('pos_cash_details_open', expanded ? '1' : '0'); } catch (e) {}
    };
    let initial = true;
    try { initial = (localStorage.getItem('pos_cash_details_open') ?? '1') === '1'; } catch (e) {}
    setExpanded(initial);
    cashToggle.addEventListener('click', () => setExpanded(cashContent.classList.contains('hidden')));
  }
});

// Mostrar campo de método de pago para ingresos y egresos; categoría solo para egresos
const movementType = document.getElementById('movementType');
const paymentMethodField = document.getElementById('paymentMethodField');
const egresoTypeField = document.getElementById('egresoTypeField');
if (movementType && paymentMethodField && egresoTypeField) {
  movementType.addEventListener('change', function() {
    // Método de pago: siempre visible (ingreso y egreso)
    paymentMethodField.classList.remove('hidden');
    
    // Categoría: solo para egresos
    if (this.value === 'egreso') {
      egresoTypeField.classList.remove('hidden');
    } else {
      egresoTypeField.classList.add('hidden');
    }
  });
  // Trigger inicial
  paymentMethodField.classList.remove('hidden');
  if (movementType.value === 'egreso') {
    egresoTypeField.classList.remove('hidden');
  }
}

// Máscara y normalización de moneda ($ y puntos miles)
function formatCurrencyDisplay(value) {
  // limpiar todo excepto dígitos y separadores
  let v = (value || '').toString().replace(/[^\d.,]/g, '');
  // si tiene coma, tratarla como decimal, si no, usar últimos dos dígitos como decimales opcional
  let hasComma = v.includes(',');
  let parts = v.split(hasComma ? ',' : '.');
  let int = parts[0].replace(/\D/g, '');
  let dec = parts[1] ? parts[1].replace(/\D/g, '').slice(0,2) : '';
  // agregar puntos de miles
  int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return '$ ' + int + (dec ? ',' + dec : '');
}

function parseCurrencyToNumber(value) {
  // de "$ 12.345,67" a "12345.67"
  if (!value) return '';
  let v = value.toString().replace(/[^\d.,]/g, '');
  // reemplazar puntos (miles) por nada y coma decimal por punto
  v = v.replace(/\./g, '').replace(',', '.');
  return v;
}

function attachCurrencyInputs() {
  const inputs = document.querySelectorAll('input.currency-input');
  inputs.forEach(inp => {
    // Al enfocar, mostrar el valor sin formato para facilitar edición
    inp.addEventListener('focus', (e) => {
      e.target.value = parseCurrencyToNumber(e.target.value);
    });
    // Al salir del campo, formatear con $ y puntos de miles
    inp.addEventListener('blur', (e) => {
      e.target.value = formatCurrencyDisplay(e.target.value);
    });
  });

  // Normalizar antes de enviar cualquier formulario de la página
  const forms = document.querySelectorAll('form');
  forms.forEach(f => {
    f.addEventListener('submit', () => {
      const curInputs = f.querySelectorAll('input.currency-input');
      curInputs.forEach(ci => {
        ci.value = parseCurrencyToNumber(ci.value);
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', attachCurrencyInputs);

// Ayuda de cambio POS
function formatPlainCurrency(n) {
  // n es número o string numérico
  if (n === '' || n === null || typeof n === 'undefined') return '$ 0';
  const parts = Number(n).toFixed(2).split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return '$ ' + parts[0] + ',' + parts[1];
}

function updateCheckoutHelper() {
  const box = document.getElementById('checkoutHelperBox');
  if (!box) return;
  const form = document.getElementById('checkoutForm');
  const cartTotal = Number(form?.dataset?.total || 0);
  const payTypeEl = document.getElementById('payType');
  const type = payTypeEl?.value || 'cash';
  const cashVal = parseFloat(parseCurrencyToNumber(document.getElementById('paymentCash')?.value || '0')) || 0;
  const virtVal = parseFloat(parseCurrencyToNumber(document.getElementById('paymentVirtual')?.value || '0')) || 0;

  // Si hay gateway activo (badge visible), total a pagar es solo la comisión
  const gatewayActive = !document.getElementById('gatewayBadge')?.classList.contains('hidden');
  const total = gatewayActive ? (type === 'cash' ? cashVal : virtVal) : cartTotal;

  let paid = 0;
  let change = 0;
  let label = 'Cambio:';
  let faltan = 0;

  if (type === 'cash') {
    paid = cashVal;
    change = cashVal - total;
  } else if (type === 'virtual') {
    paid = virtVal;
    change = 0; // virtual no genera cambio
  } else { // mixed
    paid = cashVal + virtVal;
    const restanteDespuesVirtual = Math.max(0, total - virtVal);
    change = cashVal - restanteDespuesVirtual;
  }

  if (paid < total) {
    label = 'Falta:';
    faltan = total - paid;
  }

  document.getElementById('helperTotal').textContent = formatPlainCurrency(total);
  document.getElementById('helperPaid').textContent = formatPlainCurrency(paid);
  document.getElementById('helperLabel').textContent = label;
  document.getElementById('helperChange').textContent = formatPlainCurrency(label === 'Falta:' ? faltan : Math.max(0, change));
}
</script>
@endsection
