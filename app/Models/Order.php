<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'product_description',
        'quantity',
        'category_id',
        'requested_by',
        'status',
        'finalized',
        'notes',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
