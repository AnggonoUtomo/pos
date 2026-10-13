<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ItemPrice extends Model
{
    use HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = ['item_id', 'unit_id', 'customer_level_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('sales.item_prices')
            ->logOnly(['item_id', 'unit_id', 'customer_level_id', 'amount', 'deleted_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
