@extends('layouts.app')

@section('content')
<div class="container" style="max-width:600px; margin:40px auto;">
    <h2>Mi Perfil</h2>

    @if(session('success'))
        <div style="background:#d4edda; color:#155724; padding:12px; border-radius:6px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:6px; margin-bottom:16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PATCH')

        <div style="margin-bottom:16px;">
            <label>Nombre</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px;">
        </div>

        <div style="margin-bottom:16px;">
            <label>Correo electrónico</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px;">
        </div>

        <hr style="margin:24px 0;">
        <p style="color:#666; font-size:14px;">Deja estos campos vacíos si no quieres cambiar la contraseña.</p>

        <div style="margin-bottom:16px;">
            <label>Contraseña actual</label>
            <input type="password" name="current_password"
                style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px;">
        </div>

        <div style="margin-bottom:16px;">
            <label>Nueva contraseña</label>
            <input type="password" name="password"
                style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px;">
        </div>

        <div style="margin-bottom:24px;">
            <label>Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation"
                style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px;">
        </div>

        <button type="submit"
            style="background:#2E75B6; color:white; padding:10px 24px; border:none; border-radius:6px; cursor:pointer; font-size:16px;">
            Guardar cambios
        </button>
    </form>
</div>
@endsection