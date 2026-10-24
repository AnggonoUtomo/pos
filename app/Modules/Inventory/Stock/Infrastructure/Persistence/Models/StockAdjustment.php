<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
