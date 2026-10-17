<?php

namespace App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['demo_key'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'posted_from_version' => 'integer', 'posted_at' => 'datetime', 'total_value' => 'decimal:6'];
    }
}
