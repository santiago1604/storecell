<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'device_description',
        'issue_description',
        'repair_description',
        'parts_cost',
        'total_cost',
        'received_by',
        'technician_id',
        'status',
        'delivered_at',
        'is_warranty',
        'warranty_returned_at',
        'warranty_notes',
    ];

    protected $casts = [
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'delivered_at' => 'datetime',
        'is_warranty' => 'boolean',
        'warranty_returned_at' => 'datetime',
    ];

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Suma de abonos (depósitos) registrados para esta reparación
    public function getDepositTotalAttribute(): float
    {
        $pattern = "Abono reparación - {$this->customer_name} - {$this->device_description}%";
        return (float) \App\Models\CashMovement::where('type','deposit')
            ->where('description','like',$pattern)
            ->sum('amount');
    }

    // Suma de pagos al entregar registrados en caja
    public function getPaidTotalAttribute(): float
    {
        $pattern = "Reparación entregada - {$this->customer_name} - {$this->device_description}%";
        return (float) \App\Models\CashMovement::where('type','ingreso')
            ->where('description','like',$pattern)
            ->sum('amount');
    }

    // Restante respecto al total_cost si existe
    public function getRemainingAttribute(): ?float
    {
        if ($this->total_cost === null) return null;
        $paid = $this->deposit_total + $this->paid_total;
        $remaining = (float) $this->total_cost - $paid;
        return $remaining > 0 ? $remaining : 0.0;
    }
}
