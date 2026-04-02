@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-3">Categorías</h1>
<div class="grid grid-cols-2 gap-4">
  <form method="POST" action="{{ route('categories.store') }}" class="bg-white border rounded p-3">
    @csrf
    <label class="text-sm">Nombre</label>
    <input name="name" class="border p-2 w-full mb-2" required>
    <button class="bg-black text-white px-4 py-2 rounded">Agregar</button>
  </form>
  <div class="bg-white border rounded p-3">
    <ul class="list-disc pl-6">
      @foreach($categories as $c)
      <li class="flex items-center justify-between">
        <span>{{ $c->name }}</span>
        <form method="POST" action="{{ route('categories.destroy',$c) }}">
          @csrf @method('DELETE')
          <button class="text-red-600">Eliminar</button>
        </form>
      </li>
      @endforeach
    </ul>
  </div>
</div>
@endsection
