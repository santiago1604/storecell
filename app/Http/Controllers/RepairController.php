<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RepairController extends Controller
{
    public function index(Request $r)
    {
        $status = $r->input('status');
        $user = Auth::user();
        
        $repairs = Repair::query()
            ->with(['receivedBy', 'technician'])
            ->when($status, fn($q) => $q->where('status', $status))
            // Si es técnico, mostrar solo las asignadas a él o pendientes
            ->when($user->role === 'technician', function($q) use ($user) {
                $q->where(function($w) use ($user) {
                    $w->where('technician_id', $user->id)
                      ->orWhere('status', 'pending');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($r->query());
        
        $technicians = User::withoutTrashed()->where('role', 'technician')->where('blocked', false)->get();
        
        return view('repairs.index', compact('repairs', 'status', 'technicians'));
    }

    public function history(Request $r)
    {
        $user = Auth::user();
        
        // Solo admin y técnicos pueden acceder
        if (!in_array($user->role, ['admin', 'technician'])) {
            abort(403, 'No autorizado');
        }

        $query = Repair::query()
            ->with(['receivedBy', 'technician']);

        // Si es técnico, solo ver sus propias reparaciones
        if ($user->role === 'technician') {
            $query->where('technician_id', $user->id);
        }

        // Filtros
        if ($r->filled('technician_id')) {
            $query->where('technician_id', $r->technician_id);
        }

        if ($r->filled('status')) {
            $query->where('status', $r->status);
        }

        if ($r->filled('date_from')) {
            $query->whereDate('created_at', '>=', $r->date_from);
        }

        if ($r->filled('date_to')) {
            $query->whereDate('created_at', '<=', $r->date_to);
        }

        if ($r->filled('is_warranty')) {
            $query->where('is_warranty', $r->is_warranty === '1');
        }

        // Solo reparaciones que tienen precio registrado
        $repairs = $query->whereNotNull('total_cost')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($r->query());

        // Calcular totales
        $totals = [
            'repairs_count' => $repairs->total(),
            'total_amount' => $query->sum('total_cost'),
            'total_parts' => $query->sum('parts_cost'),
            'total_labor' => $query->sum('total_cost') - $query->sum('parts_cost'),
        ];

        $technicians = User::withoutTrashed()
            ->where('role', 'technician')
            ->where('blocked', false)
            ->get();

        return view('repairs.history', compact('repairs', 'technicians', 'totals'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'device_description' => 'required|string|max:255',
            'issue_description' => 'required|string|max:1000',
            'has_deposit' => 'nullable|in:1',
            'deposit_amount' => 'nullable|numeric|min:0.01',
            'deposit_payment_method' => 'nullable|in:cash,virtual',
        ]);

        $repair = Repair::create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'device_description' => $data['device_description'],
            'issue_description' => $data['issue_description'],
            'received_by' => Auth::id(),
            'status' => 'pending',
        ]);
        $registeredDeposit = false;
        if (!empty($data['has_deposit']) && !empty($data['deposit_amount']) && !empty($data['deposit_payment_method'])) {
            // Buscar sesión de caja abierta
            $session = \App\Models\CashSession::whereDate('date', now()->toDateString())
                ->whereNull('close_at')->first();
            if ($session) {
                $methodHuman = $data['deposit_payment_method'] === 'cash' ? 'Efectivo' : 'Virtual';
                $description = "Abono reparación - {$repair->customer_name} - {$repair->device_description} ({$methodHuman})";
                try {
                    \App\Models\CashMovement::create([
                        'cash_session_id' => $session->id,
                        'type' => 'deposit',
                        'amount' => (float)$data['deposit_amount'],
                        'description' => $description,
                        'payment_method' => $data['deposit_payment_method'],
                        'created_at' => now(),
                    ]);
                    $registeredDeposit = true;
                } catch (\Throwable $e) {
                    // Fallback si no existe payment_method: repetir sin columna
                    \App\Models\CashMovement::create([
                        'cash_session_id' => $session->id,
                        'type' => 'deposit',
                        'amount' => (float)$data['deposit_amount'],
                        'description' => $description,
                        'created_at' => now(),
                    ]);
                    $registeredDeposit = true;
                }
            }
        }

        $msg = 'Dispositivo recibido correctamente.';
        if ($registeredDeposit) {
            $msg .= ' Abono registrado.';
        } elseif (!empty($data['has_deposit']) && !$registeredDeposit) {
            $msg .= ' (No se pudo registrar el abono: no hay caja abierta hoy).';
        }
        return back()->with('status', $msg);
    }

    public function update(Request $r, Repair $repair)
    {
        $user = Auth::user();

        // Si es admin, técnico o vendedor y quiere registrar el precio del mantenimiento
        if (in_array($user->role, ['admin', 'technician', 'seller']) && $r->has('total_cost')) {
            $rules = [ 'total_cost' => 'required|numeric|min:0' ];
            // Solo admin/técnico pueden poner repuestos y descripción de reparación
            if (in_array($user->role, ['admin', 'technician'])) {
                $rules['repair_description'] = 'nullable|string|max:1000';
                $rules['parts_cost'] = 'nullable|numeric|min:0';
            }
            $data = $r->validate($rules);

            $update = [ 'total_cost' => $data['total_cost'] ];
            if (isset($data['repair_description'])) $update['repair_description'] = $data['repair_description'];
            if (isset($data['parts_cost'])) $update['parts_cost'] = $data['parts_cost'];
            if ($user->role === 'technician') $update['technician_id'] = $user->id;
            // Si técnico o admin completan todo, marcar como completed
            if ($user->role === 'technician' || $user->role === 'admin') {
                $update['status'] = 'completed';
            }
            $repair->update($update);
            return back()->with('status', 'Reparación actualizada.');
        }

        // Admin/seller asigna técnico o cambia estado
        $data = $r->validate([
            'technician_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:pending,in_progress,completed,delivered',
            'payment_type' => 'nullable|in:cash,digital,mixed',
            'amount' => 'nullable|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'digital_amount' => 'nullable|numeric|min:0',
        ]);

        if (isset($data['technician_id'])) {
            $repair->technician_id = $data['technician_id'];
            $repair->status = 'in_progress';
        }
        
        if (isset($data['status']) && $data['status'] === 'delivered') {
            // Validar que se haya seleccionado tipo de pago
            if (!isset($data['payment_type'])) {
                return back()->withErrors(['payment_type' => 'Debe seleccionar un tipo de pago']);
            }

            $cashAmount = 0;
            $digitalAmount = 0;

            // Calcular montos según tipo de pago
            if ($data['payment_type'] === 'mixed') {
                $cashAmount = $data['cash_amount'] ?? 0;
                $digitalAmount = $data['digital_amount'] ?? 0;
                
                if ($cashAmount <= 0 && $digitalAmount <= 0) {
                    return back()->withErrors(['amount' => 'Debe ingresar al menos un monto para pago mixto']);
                }
            } else {
                if (!isset($data['amount']) || $data['amount'] <= 0) {
                    return back()->withErrors(['amount' => 'Debe ingresar el monto recibido']);
                }
                
                if ($data['payment_type'] === 'cash') {
                    $cashAmount = $data['amount'];
                } else {
                    $digitalAmount = $data['amount'];
                }
            }

            $totalReceived = $cashAmount + $digitalAmount;

            // Obtener la sesión de caja abierta del día
            $session = \App\Models\CashSession::whereDate('date', now()->toDateString())
                ->whereNull('close_at')->first();

            if (!$session) {
                return back()->withErrors(['cash' => 'No hay caja abierta hoy. No se puede registrar el ingreso.']);
            }

            // Registrar movimientos en caja
            $description = "Reparación entregada - {$repair->customer_name} - {$repair->device_description}";
            
            if ($cashAmount > 0) {
                \App\Models\CashMovement::create([
                    'cash_session_id' => $session->id,
                    'type' => 'ingreso',
                    'amount' => $cashAmount,
                    'description' => $description . ' (Efectivo)',
                    'created_at' => now(),
                ]);
            }

            if ($digitalAmount > 0) {
                \App\Models\CashMovement::create([
                    'cash_session_id' => $session->id,
                    'type' => 'ingreso',
                    'amount' => $digitalAmount,
                    'description' => $description . ' (Virtual)',
                    'created_at' => now(),
                ]);
            }

            $repair->status = 'delivered';
            $repair->delivered_at = now();
        }
        
        $repair->save();
        return back()->with('status', 'Reparación actualizada.');
    }

    public function destroy(Repair $repair)
    {
        $repair->delete();
        return back()->with('status', 'Reparación eliminada.');
    }

    public function markAsWarranty(Request $r, Repair $repair)
    {
        // Solo admin puede marcar como garantía
        if (!in_array($r->user()->role, ['admin'])) {
            return back()->withErrors(['permission' => 'No tienes permisos para esta acción']);
        }

        // Validar que la reparación esté entregada
        if ($repair->status !== 'delivered') {
            return back()->withErrors(['status' => 'Solo se pueden marcar como garantía las reparaciones entregadas']);
        }

        $data = $r->validate([
            'warranty_notes' => 'nullable|string|max:500',
        ]);

        // Buscar y eliminar los movimientos de caja relacionados con esta reparación
        $description = "Reparación entregada - {$repair->customer_name} - {$repair->device_description}";
        
        $movements = \App\Models\CashMovement::where('description', 'like', "{$description}%")->get();
        
        foreach ($movements as $movement) {
            $movement->delete();
        }

        // Marcar la reparación como garantía y cambiar estado a 'in_progress' para volver a procesarla
        $repair->update([
            'is_warranty' => true,
            'warranty_returned_at' => now(),
            'warranty_notes' => $data['warranty_notes'] ?? null,
            'status' => 'in_progress',
            'delivered_at' => null,
        ]);

        return back()->with('status', 'Reparación marcada como garantía. Los movimientos de caja han sido eliminados y la reparación vuelve a estado "En proceso".');
    }
}
