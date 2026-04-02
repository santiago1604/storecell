@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto">
  <h1 class="text-lg sm:text-xl font-bold mb-4">🖌️ Personalización del Sistema</h1>
  
  <div class="bg-white border rounded p-4 sm:p-6">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
      @csrf
      
      <div class="mb-6">
        <h2 class="text-base sm:text-lg font-semibold mb-3">Información de la tienda</h2>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">Nombre de la tienda</label>
          <input 
            type="text" 
            name="store_name" 
            value="{{ $storeName }}" 
            class="border rounded px-3 py-2 w-full text-base"
            placeholder="POS Tienda"
          />
          <p class="text-xs text-gray-500 mt-1">Este nombre aparecerá en lugar del logo si no hay uno configurado</p>
        </div>
      </div>
      
      <div class="mb-6">
        <h2 class="text-base sm:text-lg font-semibold mb-3">Logo de la tienda</h2>
        
        @if($logo)
          <div class="mb-4 p-4 bg-gray-50 border rounded">
            <p class="text-sm text-gray-600 mb-2">Logo actual:</p>
            <img 
              src="{{ asset('storage/' . $logo) }}" 
              alt="Logo actual" 
              class="max-h-20 mb-3"
            />
            <label class="flex items-center text-sm">
              <input 
                type="checkbox" 
                name="remove_logo" 
                value="1" 
                class="mr-2"
              />
              <span class="text-red-600">Eliminar logo actual</span>
            </label>
          </div>
        @endif
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">
            {{ $logo ? 'Cambiar logo' : 'Subir logo' }}
          </label>
          <input 
            type="file" 
            name="logo" 
            accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
            class="border rounded px-3 py-2 w-full text-base"
          />
          <p class="text-xs text-gray-500 mt-1">
            Formatos permitidos: JPEG, PNG, JPG, GIF, SVG. Tamaño máximo: 2MB
          </p>
          <p class="text-xs text-gray-500">
            Recomendación: Logo con fondo transparente (PNG) de aprox. 200x60px
          </p>
        </div>
      </div>
      
      @if($errors->any())
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
          <ul class="list-disc pl-5 text-sm">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      
      <div class="flex flex-col sm:flex-row gap-2">
        <button 
          type="submit" 
          class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-medium"
        >
          💾 Guardar cambios
        </button>
        <a 
          href="{{ route('dashboard') }}" 
          class="bg-gray-300 text-gray-800 px-6 py-2 rounded hover:bg-gray-400 text-center"
        >
          Cancelar
        </a>
      </div>
    </form>
  </div>
  
  <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
    <h3 class="font-semibold text-sm mb-2">💡 Información</h3>
    <ul class="text-xs text-gray-700 space-y-1 list-disc pl-5">
      <li>El logo aparecerá en la barra de navegación superior</li>
      <li>También se mostrará en la pantalla de inicio de sesión</li>
      <li>Si no hay logo, se mostrará el nombre de la tienda</li>
      <li>Para mejor visualización, usa un logo horizontal (formato apaisado)</li>
    </ul>
  </div>
</div>
@endsection
