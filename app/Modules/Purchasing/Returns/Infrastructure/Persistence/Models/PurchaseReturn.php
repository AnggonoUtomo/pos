<?php

namespace App\Modules\Purchasing\Returns\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PurchaseReturn extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['demo_key'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'version' => 'integer'];
    }
}
