@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Abrir caja</h1>
<form method="POST" action="{{ route('cash.store') }}" class="bg-white border rounded p-3 w-96">
  @csrf
  @if($session)
    <div class="mb-2 text-sm text-gray-600">Caja abierta hoy por: <span class="font-semibold">{{ $session->opened_by }}</span></div>
    <div class="mb-2 text-sm text-gray-600">Base actual: <span class="font-semibold">${{ number_format($session->base_amount,2) }}</span></div>
    <label class="text-sm">Actualizar base</label>
    <input name="base_amount" type="text" inputmode="decimal" value="{{ $session->base_amount }}" class="border p-2 w-full mb-3 currency-input" placeholder="$ 0" required>
    <button class="bg-black text-white px-4 py-2 rounded">Guardar cambios</button>
  @else
    <label class="text-sm">Base de caja</label>
    <input name="base_amount" type="text" inputmode="decimal" class="border p-2 w-full mb-3 currency-input" placeholder="$ 0" required>
    <button class="bg-black text-white px-4 py-2 rounded">Abrir</button>
  @endif
</form>
@endsection
@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    attachCurrencyInputs();
  });

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
      inp.addEventListener('focus', (e) => {
        e.target.value = parseCurrencyToNumber(e.target.value);
      });
      inp.addEventListener('blur', (e) => {
        e.target.value = formatCurrencyDisplay(e.target.value);
      });
    });

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
