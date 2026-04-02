<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $r) {
        $creds = $r->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);
        // Rechazar si el usuario está bloqueado
        $candidate = \App\Models\User::where('email', $creds['email'])->first();
        if ($candidate && ($candidate->blocked || $candidate->deleted_at)) {
            return back()->withErrors(['email'=>'Usuario bloqueado o eliminado. Contacta al administrador.'])->onlyInput('email');
        }

        if (Auth::attempt($creds, true)) {
            $user = Auth::user();
            if ($user->blocked || $user->deleted_at) {
                Auth::logout();
                return back()->withErrors(['email'=>'Usuario bloqueado o eliminado. Contacta al administrador.'])->onlyInput('email');
            }
            $r->session()->regenerate();
            return redirect()->intended('/');
        }
        // Fallback: si por algún motivo el attempt falla, validamos manualmente
        $user = \App\Models\User::where('email', $creds['email'])->first();
        if ($user && !$user->blocked && !$user->deleted_at && \Illuminate\Support\Facades\Hash::check($creds['password'], $user->password)) {
            \Illuminate\Support\Facades\Auth::login($user, true);
            $r->session()->regenerate();
            return redirect()->intended('/');
        }
        return back()->withErrors(['email'=>'Credenciales inválidas'])->onlyInput('email');
    }

    public function logout(Request $r) {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
