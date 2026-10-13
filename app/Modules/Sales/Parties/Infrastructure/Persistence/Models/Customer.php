<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Customer extends Model
{
    use HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'name', 'customer_level_id', 'phone', 'email', 'address', 'is_active'];

    protected static function booted(): void
    {
        static::updated(function (self $customer): void {
            $contactFields = array_values(array_intersect(['phone', 'email', 'address'], array_keys($customer->getChanges())));
            if (! $customer->enableLoggingModelsEvents || $contactFields === []
                || $customer->wasChanged(['code', 'name', 'customer_level_id', 'is_active', 'deleted_at'])) {
                return;
            }
            activity('sales.customers')->performedOn($customer)->event('updated')
                ->withProperties(['changed_fields' => $contactFields])->log('updated');
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(CustomerLevel::class, 'customer_level_id')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('sales.customers')
            ->logOnly(['code', 'name', 'customer_level_id', 'is_active', 'deleted_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
