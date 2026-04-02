@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Usuarios — StoreCell</h1>
@if(session('status'))
  <div class="bg-green-100 text-green-800 px-3 py-2 rounded mb-3 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
  <div class="bg-red-100 text-red-800 px-3 py-2 rounded mb-3 text-sm">
    <ul class="list-disc pl-5">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="GET" class="mb-3 flex gap-2 items-end">
  <div>
    <label class="block text-xs text-gray-600">Buscar</label>
    <input name="q" value="{{ $q ?? '' }}" class="border rounded px-2 py-1" placeholder="Nombre o email" />
  </div>
  <div>
    <label class="block text-xs text-gray-600">Rol</label>
    <select name="role" class="border rounded px-2 py-1">
      <option value="">Todos</option>
      <option value="seller" {{ ($role ?? '')==='seller'?'selected':'' }}>Vendedor</option>
      <option value="admin" {{ ($role ?? '')==='admin'?'selected':'' }}>Administrador</option>
      <option value="technician" {{ ($role ?? '')==='technician'?'selected':'' }}>Técnico</option>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-600">Estado</label>
    <select name="status" class="border rounded px-2 py-1">
      <option value="all" {{ ($status ?? 'all')==='all'?'selected':'' }}>Todos</option>
      <option value="active" {{ ($status ?? '')==='active'?'selected':'' }}>Activos</option>
      <option value="blocked" {{ ($status ?? '')==='blocked'?'selected':'' }}>Bloqueados</option>
    </select>
  </div>
  <button class="bg-gray-800 text-white px-3 py-1 rounded">Filtrar</button>
</form>

<div class="grid grid-cols-2 gap-4">
  <div class="bg-white border rounded p-3">
    <h2 class="font-semibold mb-2">Crear usuario</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-2">
      @csrf
      <div>
        <label class="block text-xs text-gray-600">Nombre</label>
        <input name="name" value="{{ old('name') }}" class="border rounded px-2 py-1 w-full" />
        @error('name')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="border rounded px-2 py-1 w-full" />
        @error('email')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Contraseña</label>
        <input name="password" type="password" class="border rounded px-2 py-1 w-full" />
        @error('password')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-xs text-gray-600">Rol</label>
        <select name="role" class="border rounded px-2 py-1 w-full">
          <option value="seller" {{ old('role')==='seller'?'selected':'' }}>Vendedor</option>
          <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Administrador</option>
          <option value="technician" {{ old('role')==='technician'?'selected':'' }}>Técnico</option>
        </select>
        @error('role')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
      </div>
      <button class="bg-blue-600 text-white px-3 py-1 rounded">Crear</button>
    </form>
  </div>

  <div class="bg-white border rounded p-3">
    <h2 class="font-semibold mb-2">Listado</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 text-left">Nombre</th>
            <th class="p-2 text-left">Email</th>
            <th class="p-2 text-left">Rol</th>
            <th class="p-2 text-left">Estado</th>
            <th class="p-2 text-left">Creado</th>
            <th class="p-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            <tr class="border-t">
              <td class="p-2">{{ $u->name }}</td>
              <td class="p-2">{{ $u->email }}</td>
              <td class="p-2">
                @if($u->role === 'seller')
                  <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">Vendedor</span>
                @elseif($u->role === 'admin')
                  <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs">Administrador</span>
                @else
                  <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">Técnico</span>
                @endif
              </td>
              <td class="p-2">
                @if($u->blocked)
                  <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Bloqueado</span>
                @else
                  <span class="text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-xs">Activo</span>
                @endif
              </td>
              <td class="p-2 text-xs text-gray-600">{{ $u->created_at->format('d/m/Y') }}</td>
              <td class="p-2">
                <div class="flex gap-1">
                  <button 
                    onclick="editUser({{ $u->id }}, '{{ $u->name }}', '{{ $u->email }}', '{{ $u->role }}')" 
                    class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700">
                    Editar
                  </button>
                  <form method="POST" action="{{ route('admin.users.toggle', $u->id) }}" class="inline">
                    @csrf
                    <button class="px-2 py-1 rounded text-xs {{ $u->blocked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white">
                      {{ $u->blocked ? 'Desbloquear' : 'Bloquear' }}
                    </button>
                  </form>
                  @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" class="inline" onsubmit="return confirm('¿Eliminar usuario?')">
                      @csrf @method('DELETE')
                      <button class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">Eliminar</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="p-2 text-sm text-gray-600 text-center">Sin usuarios.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
  </div>
</div>

<!-- Modal de edición -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg p-6 w-full max-w-md">
    <h3 class="text-lg font-semibold mb-4">Editar Usuario</h3>
    <form id="editForm" method="POST">
      @csrf @method('PATCH')
      <div class="space-y-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Nombre</label>
          <input id="editName" name="name" class="border rounded px-3 py-2 w-full" required />
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Email</label>
          <input id="editEmail" name="email" type="email" class="border rounded px-3 py-2 w-full" required />
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Rol</label>
          <select id="editRole" name="role" class="border rounded px-3 py-2 w-full">
            <option value="seller">Vendedor</option>
            <option value="admin">Administrador</option>
            <option value="technician">Técnico</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Nueva Contraseña (opcional)</label>
          <input id="editPassword" name="password" type="password" class="border rounded px-3 py-2 w-full" placeholder="Dejar vacío para no cambiar" />
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
        <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function editUser(id, name, email, role) {
  document.getElementById('editForm').action = `/users/${id}`;
  document.getElementById('editName').value = name;
  document.getElementById('editEmail').value = email;
  document.getElementById('editRole').value = role;
  document.getElementById('editPassword').value = '';
  document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
}

// Cerrar modal al hacer clic fuera de él
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeEditModal();
  }
});
</script>

@endsection
