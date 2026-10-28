<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeldCart extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'pos_held_carts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'version' => 'integer', 'payload' => 'array', 'line_snapshots' => 'array'];
    }
}
