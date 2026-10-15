<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PaymentReceipt extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $guarded = [];
}
