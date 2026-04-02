<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id','description','stock_qty','unit_cost','sale_price','barcode','active'
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function saleItems() {
        return $this->hasMany(\App\Models\SaleItem::class);
    }
}
