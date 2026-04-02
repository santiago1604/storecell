<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>POS Tienda</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Mejoras para móvil */
    @media (max-width: 640px) {
      input, select, textarea, button {
        font-size: 16px !important; /* Evita zoom en iOS */
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Menú móvil hamburguesa -->
  <nav class="bg-white border-b sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-2 sm:px-4">
      <div class="flex items-center justify-between h-14">
        @php
          $logo = \App\Models\Setting::get('logo_path');
          $storeName = \App\Models\Setting::get('store_name', 'POS Tienda');
        @endphp
        <div class="flex items-center">
          @if($logo)
            <img 
              src="{{ asset('storage/' . $logo) }}" 
              alt="{{ $storeName }}" 
              class="h-8 sm:h-10 max-w-[150px] sm:max-w-[200px] object-contain"
            />
          @else
            <span class="font-bold text-base sm:text-lg">{{ $storeName }}</span>
          @endif
        </div>
        @auth
          <!-- Menú móvil -->
          <button id="mobileMenuBtn" class="md:hidden p-2 rounded hover:bg-gray-100" onclick="toggleMobileMenu()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
          
          <!-- Menú desktop -->
          <div class="hidden md:flex items-center gap-2 lg:gap-4 text-sm flex-1 ml-4">
            <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('pos.index') }}">POS</a>
            @if(in_array(auth()->user()->role, ['seller','admin']))
              <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('orders.index') }}">Pedidos</a>
            @endif
            @if(in_array(auth()->user()->role, ['admin', 'technician']))
              <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('repairs.index') }}">Reparaciones</a>
            @endif
            @if(auth()->user()->role==='admin')
              <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('products.index') }}">Productos</a>
              <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('cash.open') }}">Caja</a>
              <a class="hover:text-blue-600 whitespace-nowrap" href="{{ route('dashboard') }}">Dashboard</a>
              <a class="hover:text-blue-600 whitespace-nowrap hidden xl:inline" href="{{ route('settings.index') }}">Personalizar</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="ml-auto">
              @csrf
              <button class="text-red-600 hover:text-red-700 whitespace-nowrap">Salir</button>
            </form>
          </div>
        @endauth
      </div>
    </div>
    
    <!-- Menú móvil desplegable -->
    @auth
    <div id="mobileMenu" class="hidden md:hidden border-t bg-white">
      <div class="px-2 py-2 space-y-1">
        <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('pos.index') }}">POS</a>
        @if(in_array(auth()->user()->role, ['seller','admin']))
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('orders.index') }}">Pedidos</a>
        @endif
        @if(in_array(auth()->user()->role, ['admin', 'technician']))
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('repairs.index') }}">Reparaciones</a>
        @endif
        @if(auth()->user()->role==='admin')
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('products.index') }}">Productos</a>
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('cash.open') }}">Caja</a>
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('dashboard') }}">Dashboard</a>
          <a class="block px-3 py-2 rounded hover:bg-gray-100" href="{{ route('settings.index') }}">Personalizar</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="block w-full text-left px-3 py-2 rounded hover:bg-red-50 text-red-600">Salir</button>
        </form>
      </div>
    </div>
    @endauth
  </nav>
  
  <main class="max-w-7xl mx-auto p-2 sm:p-4">
    @if(session('ok'))
      <div class="bg-emerald-100 text-emerald-800 p-2 sm:p-3 rounded mb-3 text-sm">{{ session('ok') }}</div>
    @endif
    @if(session('err'))
      <div class="bg-red-100 text-red-800 p-2 sm:p-3 rounded mb-3 text-sm">{{ session('err') }}</div>
    @endif
    @yield('content')
  </main>
  
  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }
    
    // Cerrar menú al hacer clic en un enlace
    document.addEventListener('DOMContentLoaded', function() {
      const mobileLinks = document.querySelectorAll('#mobileMenu a');
      mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
          document.getElementById('mobileMenu').classList.add('hidden');
        });
      });
    });
  </script>
  
  @yield('scripts')
</body>
</html>
