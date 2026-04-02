<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id','cash_session_id','sale_number','total',
        'payment_cash','payment_virtual','created_at','updated_at',
        'pending_delete','requested_by','delete_requested_at'
    ];

    protected $casts = [
        'pending_delete' => 'boolean',
        'delete_requested_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items() {
        return $this->hasMany(\App\Models\SaleItem::class);
    }
    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function requestedBy() {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }
}
