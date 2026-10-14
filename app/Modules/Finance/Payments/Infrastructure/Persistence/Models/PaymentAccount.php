<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PaymentAccount extends Model
{
    use HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'name', 'type', 'bank_name', 'account_number', 'account_holder', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updated(function (self $account): void {
            $fields = array_values(array_intersect(['bank_name', 'account_number', 'account_holder'], array_keys($account->getChanges())));
            if (! $account->enableLoggingModelsEvents || $fields === [] || $account->wasChanged(['code', 'name', 'type', 'is_active', 'deleted_at'])) {
                return;
            }
            activity('finance.payment_accounts')->performedOn($account)->event('updated')
                ->withProperties(['changed_fields' => $fields])->log('updated');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('finance.payment_accounts')
            ->logOnly(['code', 'name', 'type', 'is_active', 'deleted_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
