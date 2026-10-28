<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
