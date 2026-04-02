@extends('layouts.app')
@section('content')
<h1 class="text-lg sm:text-xl font-bold mb-3">Productos</h1>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-4">
  <div class="lg:col-span-5">
    <form method="POST" action="{{ route('products.store') }}" class="bg-white border rounded p-3 sm:p-4">
      @csrf
      <div class="mb-3">
        <label class="text-sm font-medium">Descripción</label>
        <input name="description" class="border p-2 w-full rounded text-base" required>
      </div>
      <div class="mb-3">
        <label class="text-sm font-medium">Categoría</label>
        <div class="flex flex-col sm:flex-row gap-2">
          <select id="category_select" name="category_id" class="border p-2 w-full rounded text-base" required>
            <option value="">-- Seleccionar --</option>
            @foreach($categories as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
          <button type="button" onclick="openCategoryModal()" class="bg-blue-600 text-white px-3 py-2 rounded whitespace-nowrap hover:bg-blue-700 text-sm sm:text-base">
            + Nueva
          </button>
          <button type="button" onclick="openManageCategoriesModal()" class="bg-gray-600 text-white px-3 py-2 rounded whitespace-nowrap hover:bg-gray-700 text-sm sm:text-base" title="Gestionar categorías">
            📋 Gestionar
          </button>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
        <div><label class="text-sm font-medium">Stock</label><input name="stock_qty" type="number" min="0" class="border p-2 w-full rounded text-base" value="0"></div>
        <div><label class="text-sm font-medium">Costo</label><input name="unit_cost" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="" placeholder="$ 0"></div>
        <div><label class="text-sm font-medium">Precio</label><input name="sale_price" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="" placeholder="$ 0"></div>
      </div>
      <div class="mb-3"><label class="text-sm font-medium">Código de barras</label><input name="barcode" class="border p-2 w-full rounded text-base"></div>
      <button class="w-full sm:w-auto bg-black text-white px-6 py-2 rounded hover:bg-gray-800">Agregar</button>
    </form>
  </div>
  <div class="lg:col-span-7">
    <!-- Paneles de alerta de stock -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
      <div class="border rounded bg-white p-3">
        <h3 class="text-sm font-semibold mb-2 flex items-center justify-between">
          <a href="{{ route('products.index', ['stock'=>0]) }}" class="hover:text-red-700">
            🛑 Sin stock ({{ $outOfStock->count() }})
          </a>
          @if($outOfStock->count())<span class="text-xs bg-red-600 text-white px-2 py-0.5 rounded">Prioridad</span>@endif
        </h3>
        @if($outOfStock->count())
          <ul class="text-xs max-h-40 overflow-y-auto space-y-1">
            @foreach($outOfStock as $p0)
              <li class="flex justify-between">
                <span class="truncate" title="{{ $p0->description }}">{{ $p0->description }}</span>
                <span class="text-red-600 font-bold">0</span>
              </li>
            @endforeach
          </ul>
        @else
          <p class="text-xs text-gray-500">Todo con stock disponible.</p>
        @endif
      </div>
      <div class="border rounded bg-white p-3">
        <h3 class="text-sm font-semibold mb-2 flex items-center justify-between">
          <a href="{{ route('products.index', ['stock'=>2, 'low_only'=>1]) }}" class="hover:text-yellow-700">
            ⚠️ Stock bajo ({{ $lowStock->count() }})
          </a>
          @if($lowStock->count())<span class="text-xs bg-yellow-400 text-black px-2 py-0.5 rounded">Revisar</span>@endif
        </h3>
        @if($lowStock->count())
          <ul class="text-xs max-h-40 overflow-y-auto space-y-1">
            @foreach($lowStock as $pl)
              <li class="flex justify-between {{ $pl->stock_qty <= 1 ? 'text-red-700 font-semibold' : '' }}">
                <span class="truncate" title="{{ $pl->description }}">{{ $pl->description }}</span>
                <span>{{ $pl->stock_qty }}</span>
              </li>
            @endforeach
          </ul>
        @else
          <p class="text-xs text-gray-500">Sin críticos.</p>
        @endif
      </div>
    </div>
    <form method="GET" action="{{ route('products.index') }}" class="mb-3 bg-white p-3 rounded border space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <div>
          <label class="text-sm font-medium block mb-1">Buscar producto/código:</label>
          <input type="text" name="search" value="{{ $search ?? '' }}" class="border p-2 w-full rounded text-base" placeholder="Nombre o código..." />
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">Categoría:</label>
          <select name="category" class="border p-2 w-full rounded text-base">
            <option value="">-- Todas --</option>
            @foreach($categories as $c)
              <option value="{{ $c->id }}" {{ ($categoryFilter ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">Stock menor o igual a:</label>
          <input type="number" name="stock" value="{{ $stock ?? '' }}" class="border p-2 w-full rounded text-base" min="0" placeholder="Ej: 2" />
        </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-2">
        <button class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">🔍 Buscar</button>
        @if(request()->hasAny(['stock','search','category']))
          <a href="{{ route('products.index') }}" class="w-full sm:w-auto text-center bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Limpiar filtros</a>
        @endif
      </div>
    </form>
    
    @if($products->where('stock_qty','<=',2)->count())
      <div class="mb-3 bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded font-bold text-sm">
        ¡Alerta! Hay productos con stock menor o igual a 2. Considera pedir al distribuidor.
      </div>
    @endif
    
    <!-- Vista de tabla (desktop) -->
    <div class="hidden sm:block overflow-auto border rounded bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 text-left">Descripción</th>
            <th class="p-2">Cat.</th>
            <th class="p-2 text-right">Stock</th>
            <th class="p-2 text-right">Costo</th>
            <th class="p-2 text-right">Precio</th>
            <th class="p-2"></th>
          </tr>
        </thead>
        <tbody>
        @foreach($products as $p)
        <!-- Fila de visualización -->
        <tr id="row-view-{{ $p->id }}" class="border-t {{ $p->stock_qty <= 2 ? 'bg-red-50' : '' }}">
          <td class="p-2 {{ $p->stock_qty <= 2 ? 'text-red-600 font-bold' : '' }}">{{ $p->description }}</td>
          <td class="p-2">{{ $p->category->name ?? '-' }}</td>
          <td class="p-2 text-right {{ $p->stock_qty <= 2 ? 'text-red-600 font-bold' : '' }}">
            {{ $p->stock_qty }}
            @if($p->stock_qty == 0)
              <span class="ml-1 px-2 py-1 bg-red-600 text-white text-xs rounded">¡Sin stock!</span>
            @elseif($p->stock_qty <= 2)
              <span class="ml-1 px-2 py-1 bg-yellow-400 text-black text-xs rounded">¡Stock bajo!</span>
            @endif
          </td>
          <td class="p-2 text-right">{{ number_format($p->unit_cost,2) }}</td>
          <td class="p-2 text-right">{{ number_format($p->sale_price,2) }}</td>
          <td class="p-2 text-right flex items-center justify-end gap-3">
            <button type="button" class="text-blue-600 hover:text-blue-800" onclick="toggleEdit({{ $p->id }}, true)">Editar</button>
            <form method="POST" action="{{ route('products.destroy', $p) }}">
              @csrf @method('DELETE')
              <button class="text-red-600 hover:text-red-800">Eliminar</button>
            </form>
          </td>
        </tr>
        <!-- Fila de edición -->
        <tr id="row-edit-{{ $p->id }}" class="border-t hidden bg-yellow-50">
          <td colspan="6" class="p-2">
            <form method="POST" action="{{ route('products.update', $p) }}" class="flex flex-col gap-2 sm:gap-3">
              @csrf @method('PATCH')
              <input type="hidden" name="category_id" value="{{ $p->category_id }}">
              <input type="hidden" name="barcode" value="{{ $p->barcode }}">
              <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                <div class="sm:col-span-2">
                  <label class="text-xs text-gray-600">Descripción</label>
                  <input name="description" class="border p-2 w-full rounded text-base" value="{{ $p->description }}" required>
                </div>
                <div>
                  <label class="text-xs text-gray-600">Stock</label>
                  <input name="stock_qty" type="number" min="0" class="border p-2 w-full rounded text-base" value="{{ $p->stock_qty }}" required>
                </div>
                <div>
                  <label class="text-xs text-gray-600">Costo</label>
                  <input name="unit_cost" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="{{ number_format($p->unit_cost,2) }}" required>
                </div>
                <div>
                  <label class="text-xs text-gray-600">Precio</label>
                  <input name="sale_price" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="{{ number_format($p->sale_price,2) }}" required>
                </div>
              </div>
              <div class="flex items-center gap-2 justify-end">
                <button type="button" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300" onclick="toggleEdit({{ $p->id }}, false)">Cancelar</button>
                <button class="px-5 py-2 rounded bg-green-600 text-white hover:bg-green-700">Guardar</button>
              </div>
            </form>
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    
    <!-- Vista de tarjetas (móvil) -->
    <div class="sm:hidden space-y-3">
      @foreach($products as $p)
      <div class="bg-white border rounded p-3 {{ $p->stock_qty <= 2 ? 'border-red-400 bg-red-50' : '' }}" id="card-view-{{ $p->id }}">
        <div class="flex justify-between items-start mb-2">
          <div class="flex-1">
            <h3 class="font-semibold {{ $p->stock_qty <= 2 ? 'text-red-600' : '' }}">{{ $p->description }}</h3>
            <p class="text-xs text-gray-500">{{ $p->category->name ?? '-' }}</p>
          </div>
          @if($p->stock_qty == 0)
            <span class="px-2 py-1 bg-red-600 text-white text-xs rounded">¡Sin stock!</span>
          @elseif($p->stock_qty <= 2)
            <span class="px-2 py-1 bg-yellow-400 text-black text-xs rounded">¡Stock bajo!</span>
          @endif
        </div>
        <div class="grid grid-cols-3 gap-2 text-sm mb-2">
          <div>
            <span class="text-gray-500 block text-xs">Stock</span>
            <span class="font-bold {{ $p->stock_qty <= 2 ? 'text-red-600' : '' }}">{{ $p->stock_qty }}</span>
          </div>
          <div>
            <span class="text-gray-500 block text-xs">Costo</span>
            <span class="font-medium">{{ number_format($p->unit_cost,2) }}</span>
          </div>
          <div>
            <span class="text-gray-500 block text-xs">Precio</span>
            <span class="font-medium">{{ number_format($p->sale_price,2) }}</span>
          </div>
        </div>
        <div class="flex gap-2 mb-2">
          <button type="button" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700" onclick="toggleEditCard({{ $p->id }}, true)">Editar</button>
          <form method="POST" action="{{ route('products.destroy', $p) }}" class="flex-1">
            @csrf @method('DELETE')
            <button class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Eliminar</button>
          </form>
        </div>
      </div>
      <!-- Card de edición (móvil) -->
      <div class="hidden bg-yellow-50 border rounded p-3" id="card-edit-{{ $p->id }}">
        <form method="POST" action="{{ route('products.update', $p) }}" class="space-y-2">
          @csrf @method('PATCH')
          <input type="hidden" name="category_id" value="{{ $p->category_id }}">
          <input type="hidden" name="barcode" value="{{ $p->barcode }}">
          <div>
            <label class="text-xs text-gray-600">Descripción</label>
            <input name="description" class="border p-2 w-full rounded text-base" value="{{ $p->description }}" required>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div>
              <label class="text-xs text-gray-600">Stock</label>
              <input name="stock_qty" type="number" min="0" class="border p-2 w-full rounded text-base" value="{{ $p->stock_qty }}" required>
            </div>
            <div>
              <label class="text-xs text-gray-600">Costo</label>
              <input name="unit_cost" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="{{ number_format($p->unit_cost,2) }}" required>
            </div>
            <div>
              <label class="text-xs text-gray-600">Precio</label>
              <input name="sale_price" type="text" inputmode="decimal" class="border p-2 w-full rounded text-base currency-input" value="{{ number_format($p->sale_price,2) }}" required>
            </div>
          </div>
          <div class="flex gap-2">
            <button type="button" class="flex-1 bg-gray-200 px-3 py-2 rounded" onclick="toggleEditCard({{ $p->id }}, false)">Cancelar</button>
            <button class="flex-1 bg-green-600 text-white px-3 py-2 rounded">Guardar</button>
          </div>
        </form>
      </div>
      @endforeach
    </div>
    
    <div class="mt-3">{{ $products->links() }}</div>
  </div>
</div>

<!-- Modal para agregar categoría -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-lg p-4 sm:p-6 w-full max-w-md">
    <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Nueva Categoría</h3>
    <form id="categoryForm" onsubmit="saveCategory(event)">
      <div class="mb-4">
        <label class="block text-sm text-gray-600 mb-1 font-medium">Nombre de la categoría</label>
        <input id="categoryName" type="text" class="border rounded px-3 py-2 w-full text-base" required placeholder="Ej: Smartphones, Accesorios..." />
      </div>
      <div class="flex flex-col sm:flex-row gap-2">
        <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          Guardar
        </button>
        <button type="button" onclick="closeCategoryModal()" class="w-full sm:w-auto bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
          Cancelar
        </button>
      </div>
    </form>
    <div id="categoryError" class="hidden mt-3 bg-red-100 text-red-700 px-3 py-2 rounded text-sm"></div>
    <div id="categorySuccess" class="hidden mt-3 bg-green-100 text-green-700 px-3 py-2 rounded text-sm"></div>
  </div>
</div>

<!-- Modal para gestionar categorías -->
<div id="manageCategoriesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-lg p-4 sm:p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Gestionar Categorías</h3>
    <div id="categoriesList" class="max-h-60 sm:max-h-96 overflow-y-auto mb-4 border rounded">
      @foreach($categories as $c)
        <div class="flex items-center justify-between p-3 border-b hover:bg-gray-50" data-category-id="{{ $c->id }}">
          <span class="font-medium text-sm sm:text-base">{{ $c->name }}</span>
          <button type="button" onclick="deleteCategory({{ $c->id }}, '{{ $c->name }}')" class="bg-red-600 text-white px-3 py-1.5 rounded text-xs sm:text-sm hover:bg-red-700 whitespace-nowrap">
            Eliminar
          </button>
        </div>
      @endforeach
    </div>
    <div id="manageError" class="hidden mb-3 bg-red-100 text-red-700 px-3 py-2 rounded text-sm"></div>
    <div id="manageSuccess" class="hidden mb-3 bg-green-100 text-green-700 px-3 py-2 rounded text-sm"></div>
    <div class="flex justify-end">
      <button type="button" onclick="closeManageCategoriesModal()" class="w-full sm:w-auto bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
        Cerrar
      </button>
    </div>
  </div>
</div>

<script>
// Formateo de moneda: solo al terminar (blur) y normalizar en submit
document.addEventListener('DOMContentLoaded', () => {
  attachCurrencyInputs();
});

function toggleEdit(id, on) {
  const view = document.getElementById('row-view-' + id);
  const edit = document.getElementById('row-edit-' + id);
  if (!view || !edit) return;
  if (on) {
    view.classList.add('hidden');
    edit.classList.remove('hidden');
  } else {
    edit.classList.add('hidden');
    view.classList.remove('hidden');
  }
}

function toggleEditCard(id, on) {
  const view = document.getElementById('card-view-' + id);
  const edit = document.getElementById('card-edit-' + id);
  if (!view || !edit) return;
  if (on) {
    view.classList.add('hidden');
    edit.classList.remove('hidden');
  } else {
    edit.classList.add('hidden');
    view.classList.remove('hidden');
  }
}

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
function openCategoryModal() {
  document.getElementById('categoryModal').classList.remove('hidden');
  document.getElementById('categoryName').value = '';
  document.getElementById('categoryError').classList.add('hidden');
  document.getElementById('categorySuccess').classList.add('hidden');
  document.getElementById('categoryName').focus();
}

function closeCategoryModal() {
  document.getElementById('categoryModal').classList.add('hidden');
}

function openManageCategoriesModal() {
  document.getElementById('manageCategoriesModal').classList.remove('hidden');
  document.getElementById('manageError').classList.add('hidden');
  document.getElementById('manageSuccess').classList.add('hidden');
}

function closeManageCategoriesModal() {
  document.getElementById('manageCategoriesModal').classList.add('hidden');
}

// Cerrar modales al hacer clic fuera
document.getElementById('categoryModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeCategoryModal();
  }
});

document.getElementById('manageCategoriesModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeManageCategoriesModal();
  }
});

async function saveCategory(event) {
  event.preventDefault();
  
  const name = document.getElementById('categoryName').value.trim();
  const errorDiv = document.getElementById('categoryError');
  const successDiv = document.getElementById('categorySuccess');
  const submitBtn = event.target.querySelector('button[type="submit"]');
  
  if (!name) {
    errorDiv.textContent = 'El nombre es requerido';
    errorDiv.classList.remove('hidden');
    return;
  }
  
  // Deshabilitar botón
  submitBtn.disabled = true;
  submitBtn.textContent = 'Guardando...';
  errorDiv.classList.add('hidden');
  successDiv.classList.add('hidden');
  
  try {
    const response = await fetch('{{ route("categories.store") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ name: name })
    });
    
    const data = await response.json();
    
    if (response.ok && data.success) {
      // Agregar la nueva categoría al select
      const select = document.getElementById('category_select');
      const option = new Option(data.category.name, data.category.id, true, true);
      select.add(option, select.options.length);
      
      // Agregar al modal de gestión si está abierto
      const manageModal = document.getElementById('manageCategoriesModal');
      if (!manageModal.classList.contains('hidden')) {
        const categoriesList = document.getElementById('categoriesList');
        const newRow = document.createElement('div');
        newRow.className = 'flex items-center justify-between p-2 border-b hover:bg-gray-50';
        newRow.setAttribute('data-category-id', data.category.id);
        newRow.innerHTML = `
          <span class="font-medium">${data.category.name}</span>
          <button type="button" onclick="deleteCategory(${data.category.id}, '${data.category.name}')" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
            Eliminar
          </button>
        `;
        categoriesList.appendChild(newRow);
      }
      
      // Mostrar mensaje de éxito
      successDiv.textContent = '✓ Categoría creada correctamente';
      successDiv.classList.remove('hidden');
      
      // Cerrar modal después de 1 segundo
      setTimeout(() => {
        closeCategoryModal();
      }, 1000);
    } else {
      errorDiv.textContent = data.message || 'Error al crear la categoría';
      errorDiv.classList.remove('hidden');
    }
  } catch (error) {
    errorDiv.textContent = 'Error de conexión. Intenta de nuevo.';
    errorDiv.classList.remove('hidden');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Guardar';
  }
}

async function deleteCategory(id, name) {
  if (!confirm(`¿Eliminar la categoría "${name}"?\n\nAdvertencia: Esto puede afectar los productos asociados.`)) {
    return;
  }
  
  const errorDiv = document.getElementById('manageError');
  const successDiv = document.getElementById('manageSuccess');
  
  errorDiv.classList.add('hidden');
  successDiv.classList.add('hidden');
  
  try {
    const response = await fetch(`/categories/${id}`, {
      method: 'POST', // Usar POST para compatibilidad
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ _method: 'DELETE' }) // Enviar _method: 'DELETE'
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Eliminar del select
      const select = document.getElementById('category_select');
      const optionToRemove = select.querySelector(`option[value="${id}"]`);
      if (optionToRemove) {
        optionToRemove.remove();
      }

      // Eliminar del modal de gestión
      const rowToRemove = document.querySelector(`[data-category-id="${id}"]`);
      if (rowToRemove) {
        rowToRemove.remove();
      }

      // Mostrar mensaje de éxito
      successDiv.textContent = '✓ Categoría eliminada correctamente';
      successDiv.classList.remove('hidden');

      setTimeout(() => {
        successDiv.classList.add('hidden');
      }, 3000);
    } else {
      errorDiv.textContent = data.message || 'Error al eliminar la categoría';
      errorDiv.classList.remove('hidden');
    }
  } catch (error) {
    errorDiv.textContent = 'Error de conexión. Intenta de nuevo.';
    errorDiv.classList.remove('hidden');
  }
}
</script>

@endsection
