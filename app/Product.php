<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    public $timestamps = true;

    protected $dates = ['deleted_at'];
    
    public function Category() {
        return $this->belongsTo('App\Category', 'category_id')->withTrashed();
    }

    public function Merchant() {
        return $this->belongsTo('App\Merchant', 'merchant_id')->withTrashed();
    }
}
