<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    protected $fillable = [
        'date','base_amount','opened_by','open_at','closed_by','close_at'
    ];
}
