<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'cash_session_id','type','amount','description','note','payment_method','created_at'
    ];
    
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
