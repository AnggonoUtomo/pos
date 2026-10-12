<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpening extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];
}
