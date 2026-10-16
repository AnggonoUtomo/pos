<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'sales_invoices';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payments' => 'array'];
    }
}
