<?php

namespace App\Modules\Sales\Returns\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ReturnSettlement extends Model
{
    use HasUlids;

    protected $table = 'sales_return_settlements';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'from_version' => 'integer', 'scope_revision' => 'integer'];
    }
}
