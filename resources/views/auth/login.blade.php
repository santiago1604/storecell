<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Login POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media (max-width: 640px) {
      input, button {
        font-size: 16px !important;
      }
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
  <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg w-full max-w-md">
    @php
      $logo = \App\Models\Setting::get('logo_path');
      $storeName = \App\Models\Setting::get('store_name', 'POS Tienda');
    @endphp
    
    <div class="text-center mb-6">
      @if($logo)
        <img 
          src="{{ asset('storage/' . $logo) }}" 
          alt="{{ $storeName }}" 
          class="h-16 sm:h-20 mx-auto mb-4 max-w-full object-contain"
        />
      @else
        <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ $storeName }}</h1>
      @endif
      <p class="text-gray-600 text-sm">Sistema de Punto de Venta</p>
    </div>
    
    <form method="POST" action="{{ route('login.post') }}">
      @csrf
      <h2 class="text-lg sm:text-xl font-bold mb-4">Ingresar</h2>
      @error('email')
        <div class="bg-red-100 text-red-800 p-3 rounded mb-3 text-sm">{{ $message }}</div>
      @enderror
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Email</label>
        <input 
          name="email" 
          type="email" 
          class="border rounded px-3 py-2 w-full text-base" 
          value="{{ old('email') }}" 
          required 
          autocomplete="email"
        />
      </div>
      <div class="mb-6">
        <label class="block text-sm font-medium mb-1">Contraseña</label>
        <input 
          name="password" 
          type="password" 
          class="border rounded px-3 py-2 w-full text-base" 
          required 
          autocomplete="current-password"
        />
      </div>
      <button class="w-full bg-black text-white py-3 rounded hover:bg-gray-800 font-medium text-base">
        🔓 Entrar
      </button>
    </form>
  </div>
</body>
</html>
