@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-3">
  <h1 class="text-xl font-bold">Reparaciones</h1>
  @if(in_array(auth()->user()->role, ['admin', 'technician']))
    <a href="{{ route('repairs.history') }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
      📊 Ver historial completo
    </a>
  @endif
</div>

@if(session('status'))
  <div class="bg-green-100 text-green-800 px-3 py-2 rounded mb-3 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="bg-red-100 text-red-800 px-3 py-2 rounded mb-3 text-sm">
    <ul>
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="GET" class="mb-3 flex gap-2 items-end">
  <div>
    <label class="block text-xs text-gray-600">Estado</label>
    <select name="status" class="border rounded px-2 py-1">
      <option value="">Todos</option>
      <option value="pending" {{ ($status ?? '')==='pending'?'selected':'' }}>Pendiente</option>
      <option value="in_progress" {{ ($status ?? '')==='in_progress'?'selected':'' }}>En proceso</option>
      <option value="completed" {{ ($status ?? '')==='completed'?'selected':'' }}>Completada</option>
      <option value="delivered" {{ ($status ?? '')==='delivered'?'selected':'' }}>Entregada</option>
    </select>
  </div>
  <button class="bg-gray-800 text-white px-3 py-1 rounded">Filtrar</button>
</form>

<div class="grid grid-cols-2 gap-4">
  <div class="bg-white border rounded p-3">
    <h2 class="font-semibold mb-2">Recibir dispositivo</h2>
    <form method="POST" action="{{ route('repairs.store') }}" class="space-y-2" id="repairReceiveForm">
      @csrf
      <div>
        <label class="block text-xs text-gray-600">Nombre del cliente *</label>
        <input name="customer_name" value="{{ old('customer_name') }}" class="border rounded px-2 py-1 w-full" required />
        @error('customer_name')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Teléfono *</label>
        <input name="customer_phone" value="{{ old('customer_phone') }}" class="border rounded px-2 py-1 w-full" required />
        @error('customer_phone')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Dispositivo *</label>
        <input name="device_description" value="{{ old('device_description') }}" placeholder="Ej: Samsung Galaxy S21" class="border rounded px-2 py-1 w-full" required />
        @error('device_description')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Motivo/Problema *</label>
        <textarea name="issue_description" class="border rounded px-2 py-1 w-full" rows="3" required>{{ old('issue_description') }}</textarea>
        @error('issue_description')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div class="border rounded p-2 bg-gray-50 space-y-2">
        <div class="flex items-center gap-2">
          <input type="checkbox" id="hasDeposit" name="has_deposit" value="1" class="rounded border-gray-400" {{ old('has_deposit')? 'checked':'' }} />
          <label for="hasDeposit" class="text-xs font-medium text-gray-700">Registrar abono inicial</label>
        </div>
        <div id="depositFields" class="space-y-2 {{ old('has_deposit')? '':'hidden' }}">
          <div class="flex gap-2">
            <div class="w-1/2">
              <label class="block text-xs text-gray-600">Monto abono</label>
              <input type="text" inputmode="decimal" name="deposit_amount" value="{{ old('deposit_amount') }}" class="border rounded px-2 py-1 w-full currency-input" placeholder="$ 0" />
              @error('deposit_amount')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
            </div>
            <div class="w-1/2">
              <label class="block text-xs text-gray-600">Método pago</label>
              <select name="deposit_payment_method" class="border rounded px-2 py-1 w-full">
                <option value="cash" {{ old('deposit_payment_method')==='cash'?'selected':'' }}>Efectivo</option>
                <option value="virtual" {{ old('deposit_payment_method')==='virtual'?'selected':'' }}>Virtual</option>
              </select>
              @error('deposit_payment_method')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="text-[11px] text-gray-500 italic">El abono se sumará a los totales del POS según el método elegido.</div>
        </div>
      </div>
      <button class="bg-blue-600 text-white px-3 py-1 rounded">Recibir dispositivo</button>
    </form>
  </div>

  <div class="bg-white border rounded p-3 overflow-auto" style="max-height: 600px;">
    <h2 class="font-semibold mb-2">Listado de reparaciones</h2>
    @forelse($repairs as $r)
      <div class="border-b pb-3 mb-3 {{ $r->is_warranty ? 'bg-orange-50' : '' }}">
        <div class="flex justify-between items-start mb-1">
          <div>
            <div class="font-semibold">
              {{ $r->customer_name }} - {{ $r->customer_phone }}
              @if($r->is_warranty)
                <span class="text-xs px-2 py-1 rounded bg-orange-500 text-white ml-2">🔧 GARANTÍA</span>
              @endif
            </div>
            <div class="text-sm text-gray-600">{{ $r->device_description }}</div>
          </div>
          <span class="text-xs px-2 py-1 rounded
            {{ $r->status==='pending'?'bg-yellow-100 text-yellow-800':'' }}
            {{ $r->status==='in_progress'?'bg-blue-100 text-blue-800':'' }}
            {{ $r->status==='completed'?'bg-green-100 text-green-800':'' }}
            {{ $r->status==='delivered'?'bg-gray-100 text-gray-800':'' }}
          ">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span>
        </div>
        @if($r->is_warranty && $r->warranty_notes)
          <div class="text-xs mb-2 bg-orange-100 p-2 rounded">
            <strong>Nota de garantía:</strong> {{ $r->warranty_notes }}
          </div>
        @endif
        <div class="text-sm mb-2">
          <strong>Problema:</strong> {{ $r->issue_description }}
        </div>
        

        @if($r->repair_description || $r->total_cost)
          <div class="text-sm mb-1 bg-gray-50 p-2 rounded">
            @if($r->repair_description)
              <strong>Reparación:</strong> {{ $r->repair_description }}<br>
            @endif
            @if($r->parts_cost)
              <strong>Repuestos:</strong> ${{ number_format($r->parts_cost,2) }} |
            @endif
            <strong>Total:</strong> ${{ number_format($r->total_cost,2) }}
          </div>
        @endif

        <div class="text-xs mb-2 bg-emerald-50 border border-emerald-200 p-2 rounded flex justify-between">
          <div>
            <span class="font-semibold text-emerald-800">Abono:</span>
            <span class="text-emerald-700">${{ number_format($r->deposit_total,2) }}</span>
          </div>
          <div>
            <span class="font-semibold text-gray-800">Restante:</span>
            <span class="text-gray-700">
              @if($r->remaining !== null)
                ${{ number_format($r->remaining,2) }}
              @else
                —
              @endif
            </span>
          </div>
        </div>

        @if(in_array(auth()->user()->role, ['admin','technician','seller']) && $r->status !== 'delivered' && $r->status !== 'completed')
          <form method="POST" action="{{ route('repairs.update', $r->id) }}" class="mt-2 space-y-1">
            @csrf @method('PATCH')
            @if(in_array(auth()->user()->role, ['admin','technician']))
              <input name="repair_description" placeholder="Descripción del arreglo" class="border rounded px-2 py-1 w-full text-sm" />
              <div class="flex gap-2">
                <input name="parts_cost" type="text" inputmode="decimal" placeholder="Costo repuestos" class="border rounded px-2 py-1 w-1/2 text-sm currency-input" />
                <input name="total_cost" type="text" inputmode="decimal" placeholder="Total" class="border rounded px-2 py-1 w-1/2 text-sm currency-input" required />
              </div>
            @else
              <input name="total_cost" type="text" inputmode="decimal" placeholder="Total" class="border rounded px-2 py-1 w-full text-sm currency-input" required />
            @endif
            <button class="bg-green-600 text-white px-2 py-1 rounded text-xs">Registrar precio</button>
          </form>
        @endif

        @if(in_array(auth()->user()->role, ['admin','seller']) && $r->status !== 'delivered')
          @if($r->status === 'pending')
            <form method="POST" action="{{ route('repairs.update', $r->id) }}" class="mt-2 flex gap-2">
              @csrf @method('PATCH')
              <select name="technician_id" class="border rounded px-2 py-1 text-xs">
                <option value="">Asignar técnico...</option>
                @foreach($technicians as $t)
                  <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
              </select>
              <button class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Asignar</button>
            </form>
          @endif
        @endif
          
        @if(in_array(auth()->user()->role, ['admin','seller','technician']) && $r->status === 'completed')
            @php $due = $r->remaining !== null ? $r->remaining : (($r->total_cost ?? 0) - ($r->deposit_total ?? 0)); @endphp
            <form method="POST" action="{{ route('repairs.update', $r->id) }}" class="mt-2 space-y-2 bg-blue-50 p-2 rounded" id="repairPayForm_{{ $r->id }}" data-total="{{ $due }}">
              @csrf @method('PATCH')
              <input type="hidden" name="status" value="delivered" />
              
              <div class="text-xs font-semibold text-gray-700 mb-1">Registrar entrega y pago:</div>
              
              <div>
                <label class="block text-xs text-gray-600">Tipo de pago *</label>
                <select name="payment_type" id="payment_type_{{ $r->id }}" class="border rounded px-2 py-1 text-xs w-full" required 
                  onchange="togglePaymentFields({{ $r->id }})">
                  <option value="">Seleccionar...</option>
                  <option value="cash">Efectivo</option>
                  <option value="digital">Virtual</option>
                  <option value="mixed">Mixto</option>
                </select>
              </div>

              <div id="single_field_{{ $r->id }}" style="display:none;">
                <label class="block text-xs text-gray-600">Monto recibido *</label>
                <input type="text" name="amount" inputmode="decimal" class="border rounded px-2 py-1 text-xs w-full currency-input" 
                  placeholder="$ 0" />
              </div>

              <div id="mixed_fields_{{ $r->id }}" style="display:none;">
                <div class="flex gap-2">
                  <div class="w-1/2">
                    <label class="block text-xs text-gray-600">Efectivo</label>
                    <input type="text" name="cash_amount" inputmode="decimal" class="border rounded px-2 py-1 text-xs w-full currency-input" 
                      placeholder="$ 0" />
                  </div>
                  <div class="w-1/2">
                    <label class="block text-xs text-gray-600">Virtual</label>
                    <input type="text" name="digital_amount" inputmode="decimal" class="border rounded px-2 py-1 text-xs w-full currency-input" 
                      placeholder="$ 0" />
                  </div>
                </div>
              </div>

              <div id="repairHelperBox_{{ $r->id }}" class="mt-2 text-[11px] bg-purple-50 border border-purple-200 rounded p-2 hidden">
                <div class="flex justify-between"><span>Total a pagar:</span><span id="repairHelperTotal_{{ $r->id }}" class="font-semibold"></span></div>
                <div class="flex justify-between"><span>Pagado:</span><span id="repairHelperPaid_{{ $r->id }}" class="font-semibold"></span></div>
                <div class="flex justify-between"><span id="repairHelperLabel_{{ $r->id }}">Cambio:</span><span id="repairHelperChange_{{ $r->id }}" class="font-bold"></span></div>
              </div>

              <button class="bg-green-600 text-white px-2 py-1 rounded text-xs w-full">Entregar y registrar en caja</button>
            </form>

            <script>
              function togglePaymentFields(repairId) {
                const paymentType = document.getElementById('payment_type_' + repairId).value;
                const singleField = document.getElementById('single_field_' + repairId);
                const mixedFields = document.getElementById('mixed_fields_' + repairId);
                
                if (paymentType === 'mixed') {
                  singleField.style.display = 'none';
                  mixedFields.style.display = 'block';
                  document.getElementById('repairHelperBox_' + repairId).classList.remove('hidden');
                } else if (paymentType === 'cash' || paymentType === 'digital') {
                  singleField.style.display = 'block';
                  mixedFields.style.display = 'none';
                  document.getElementById('repairHelperBox_' + repairId).classList.remove('hidden');
                } else {
                  singleField.style.display = 'none';
                  mixedFields.style.display = 'none';
                  document.getElementById('repairHelperBox_' + repairId).classList.add('hidden');
                }
                updateRepairHelper(repairId);
              }

              function formatPlainCurrency(n) {
                if (n === '' || n === null || typeof n === 'undefined') return '$ 0';
                const parts = Number(n).toFixed(2).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return '$ ' + parts[0] + ',' + parts[1];
              }

              function updateRepairHelper(repairId) {
                const form = document.getElementById('repairPayForm_' + repairId);
                if (!form) return;
                const total = Number(form.dataset.total || 0);
                const type = document.getElementById('payment_type_' + repairId).value || 'cash';
                let paid = 0, change = 0, label = 'Cambio:', faltan = 0;
                if (type === 'mixed') {
                  const cash = parseFloat(parseCurrencyToNumber(document.querySelector(`#repairPayForm_${repairId} input[name="cash_amount"]`)?.value || '0')) || 0;
                  const virt = parseFloat(parseCurrencyToNumber(document.querySelector(`#repairPayForm_${repairId} input[name="digital_amount"]`)?.value || '0')) || 0;
                  paid = cash + virt;
                  const restante = Math.max(0, total - virt);
                  change = cash - restante;
                } else if (type === 'cash') {
                  const cash = parseFloat(parseCurrencyToNumber(document.querySelector(`#repairPayForm_${repairId} input[name="amount"]`)?.value || '0')) || 0;
                  paid = cash;
                  change = cash - total;
                } else if (type === 'digital') {
                  const virt = parseFloat(parseCurrencyToNumber(document.querySelector(`#repairPayForm_${repairId} input[name="amount"]`)?.value || '0')) || 0;
                  paid = virt;
                  change = 0;
                }
                if (paid < total) { label = 'Falta:'; faltan = total - paid; }
                document.getElementById('repairHelperTotal_' + repairId).textContent = formatPlainCurrency(total);
                document.getElementById('repairHelperPaid_' + repairId).textContent = formatPlainCurrency(paid);
                document.getElementById('repairHelperLabel_' + repairId).textContent = label;
                document.getElementById('repairHelperChange_' + repairId).textContent = formatPlainCurrency(label === 'Falta:' ? faltan : Math.max(0, change));
              }
            </script>
        @endif

        <div class="mt-2 flex gap-2 text-xs text-gray-500">
          <span>Recibió: {{ $r->receivedBy?->name ?? 'Desconocido' }}</span>
          @if($r->technician)
            <span>| Técnico: {{ $r->technician->name }}</span>
          @endif
          <span>| {{ $r->created_at->format('d/m/Y H:i') }}</span>
        </div>

        @if(auth()->user()->role === 'admin')
          <form method="POST" action="{{ route('repairs.destroy', $r->id) }}" class="inline mt-1" onsubmit="return confirm('¿Eliminar reparación?')">
            @csrf @method('DELETE')
            <button class="text-red-600 text-xs">Eliminar</button>
          </form>
        @endif
      </div>
    @empty
      <div class="text-sm text-gray-600">Sin reparaciones.</div>
    @endforelse
    <div class="mt-3">{{ $repairs->links() }}</div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const chk = document.getElementById('hasDeposit');
    const fields = document.getElementById('depositFields');
    if (chk) {
      chk.addEventListener('change', () => {
        if (chk.checked) fields.classList.remove('hidden'); else fields.classList.add('hidden');
      });
    }

    // Adjuntar máscara de moneda a todos los inputs con clase currency-input
    attachCurrencyInputs();

    // Bind de eventos para actualizar ayuda de cambio en cada formulario de entrega
    document.querySelectorAll('[id^="repairPayForm_"]').forEach(form => {
      const id = form.id.split('_')[1];
      const typeSel = document.getElementById('payment_type_' + id);
      if (typeSel) typeSel.addEventListener('change', () => updateRepairHelper(id));
      ['input','blur','focus'].forEach(evt => {
        form.querySelectorAll('input.currency-input').forEach(inp => {
          inp.addEventListener(evt, () => updateRepairHelper(id));
        });
      });
      updateRepairHelper(id);
    });
  });

  // Máscara y normalización de moneda ($ y puntos miles)
  function formatCurrencyDisplay(value) {
    let v = (value || '').toString().replace(/[^\d.,]/g, '');
    let hasComma = v.includes(',');
    let parts = v.split(hasComma ? ',' : '.');
    let int = parts[0].replace(/\D/g, '');
    let dec = parts[1] ? parts[1].replace(/\D/g, '').slice(0,2) : '';
    int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return '$ ' + int + (dec ? ',' + dec : '');
  }

  function parseCurrencyToNumber(value) {
    if (!value) return '';
    let v = value.toString().replace(/[^\d.,]/g, '');
    v = v.replace(/\./g, '').replace(',', '.');
    return v;
  }

  function attachCurrencyInputs() {
    const inputs = document.querySelectorAll('input.currency-input');
    inputs.forEach(inp => {
      // Al enfocar, quitar formato para editar fácilmente
      inp.addEventListener('focus', (e) => {
        e.target.value = parseCurrencyToNumber(e.target.value);
      });
      // Al salir del campo, aplicar formato de moneda
      inp.addEventListener('blur', (e) => {
        e.target.value = formatCurrencyDisplay(e.target.value);
      });
    });

    // Normalizar antes de enviar cualquier formulario de la página de reparaciones
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
</script>
@endsection
