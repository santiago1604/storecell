<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function index(Request $r)
    {
        $q = $r->input('q');
        $role = $r->input('role');
        $status = $r->input('status'); // active|blocked|all

        $users = User::query()
            ->withoutTrashed()
            ->when($q, function($qb) use ($q) {
                $qb->where(function($w) use ($q){
                    $w->where('name','like',"%$q%")
                      ->orWhere('email','like',"%$q%");
                });
            })
            ->when($role, fn($qb)=>$qb->where('role',$role))
            ->when($status==='blocked', fn($qb)=>$qb->where('blocked',true))
            ->when($status==='active', fn($qb)=>$qb->where('blocked',false))
            ->orderBy('name')
            ->paginate(10)
            ->appends($r->query());
        return view('admin.users.index', compact('users','q','role','status'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,seller,technician',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function update(Request $r, User $user)
    {
        // Protecciones: no permitir desasignar su propio rol admin si es el último admin, ni bloquearse/eliminarse a sí mismo aquí
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,seller,technician',
        ]);

        // Evitar quedarse sin administradores
        if ($user->role === 'admin' && $data['role'] === 'seller') {
            $otherAdmins = User::withoutTrashed()->where('role','admin')->where('id','!=',$user->id)->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['role'=>'Debe existir al menos un administrador.']);
            }
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        // No permitir que se elimine a sí mismo
        if (Auth::id() === $user->id) {
            return back()->withErrors(['delete'=>'No puedes eliminar tu propia cuenta.']);
        }
        // Evitar eliminar al último admin
        if ($user->role === 'admin') {
            $otherAdmins = User::withoutTrashed()->where('role','admin')->where('id','!=',$user->id)->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['delete'=>'Debe quedar al menos un administrador.']);
            }
        }
        // Usar soft delete (eliminación lógica) para preservar la integridad de datos históricos
        $user->delete();
        return back()->with('status','Usuario eliminado correctamente. Sus registros históricos se mantienen para auditoría.');
    }

    public function toggleBlock(User $user)
    {
        // No bloquearse a sí mismo
        if (Auth::id() === $user->id) {
            return back()->withErrors(['blocked'=>'No puedes bloquear tu propia cuenta.']);
        }
        $user->blocked = !$user->blocked;
        $user->save();
        return back()->with('status', $user->blocked ? 'Usuario bloqueado.' : 'Usuario desbloqueado.');
    }
}
