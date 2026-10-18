<?php

namespace App\Modules\Sales\Returns\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'version' => 'integer', 'posted_from_version' => 'integer', 'posted_at' => 'datetime'];
    }
}
