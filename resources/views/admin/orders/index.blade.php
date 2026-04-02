@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Pedidos de clientes</h1>
@if(session('status'))
  <div class="bg-green-100 text-green-800 px-3 py-2 rounded mb-3 text-sm">{{ session('status') }}</div>
@endif

<form method="GET" class="mb-3 flex gap-2 items-end">
  <div>
    <label class="block text-xs text-gray-600">Estado</label>
    <select name="status" class="border rounded px-2 py-1">
      <option value="">Todos</option>
      <option value="pending" {{ ($status ?? '')==='pending'?'selected':'' }}>Pendiente</option>
      <option value="ordered" {{ ($status ?? '')==='ordered'?'selected':'' }}>Ordenado</option>
      <option value="received" {{ ($status ?? '')==='received'?'selected':'' }}>Recibido</option>
    </select>
  </div>
  <button class="bg-gray-800 text-white px-3 py-1 rounded">Filtrar</button>
</form>

<div class="grid grid-cols-2 gap-4">
  <div class="bg-white border rounded p-3">
    <h2 class="font-semibold mb-2">Crear pedido</h2>
    <form method="POST" action="{{ route('orders.store') }}" class="space-y-2">
      @csrf
      <div>
        <label class="block text-xs text-gray-600">Producto *</label>
        <input name="product_description" value="{{ old('product_description') }}" class="border rounded px-2 py-1 w-full" required />
        @error('product_description')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Cantidad *</label>
        <input name="quantity" type="number" value="{{ old('quantity', 1) }}" min="1" class="border rounded px-2 py-1 w-full" required />
        @error('quantity')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Categoría *</label>
        <select name="category_id" class="border rounded px-2 py-1 w-full" required>
          <option value="">Seleccione</option>
          @foreach(App\Models\Category::all() as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
        @error('category_id')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <button class="bg-blue-600 text-white px-3 py-1 rounded">Crear</button>
    </form>
  </div>

  <div class="bg-white border rounded p-3">
    <h2 class="font-semibold mb-2">Listado de pedidos</h2>
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left">Producto</th>
          <th class="p-2 text-left">Cant.</th>
          <th class="p-2 text-left">Categoría</th>
          <th class="p-2 text-left">Finalizado</th>
          <th class="p-2 text-left">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr class="border-t">
            <td class="p-2">{{ $o->product_description }}</td>
            <td class="p-2">{{ $o->quantity }}</td>
            <td class="p-2">{{ $o->category ? $o->category->name : '—' }}</td>
            <td class="p-2">
              <form method="POST" action="{{ route('orders.update', $o->id) }}" class="inline">
                @csrf @method('PATCH')
                <select name="finalized" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                  <option value="no" {{ $o->finalized==='no'?'selected':'' }}>No</option>
                  <option value="si" {{ $o->finalized==='si'?'selected':'' }}>Sí</option>
                </select>
              </form>
            </td>
            <td class="p-2">
              @if($o->finalized==='si')
                <form method="POST" action="{{ route('orders.import', $o->id) }}" class="inline">
                  @csrf
                  <button class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700" title="Crear producto / sumar stock">Agregar a productos</button>
                </form>
              @endif
              <form method="POST" action="{{ route('orders.destroy', $o->id) }}" class="inline" onsubmit="return confirm('¿Eliminar pedido?')">
                @csrf @method('DELETE')
                <button class="text-red-600 text-xs">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="p-2 text-sm text-gray-600">Sin pedidos.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="mt-3">{{ $orders->links() }}</div>
  </div>
</div>
@endsection
