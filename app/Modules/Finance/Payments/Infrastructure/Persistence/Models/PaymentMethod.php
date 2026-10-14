<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PaymentMethod extends Model
{
    use HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'name', 'type', 'payment_account_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('finance.payment_methods')
            ->logOnly(['code', 'name', 'type', 'payment_account_id', 'is_active', 'deleted_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
