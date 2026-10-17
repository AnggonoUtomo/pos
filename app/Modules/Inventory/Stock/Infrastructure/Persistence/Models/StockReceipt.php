<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StockReceipt extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $guarded = [];
}
