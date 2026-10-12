<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerLevel extends Model
{
    use HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('sales.customer_levels')
            ->logOnly(['code', 'name', 'is_active', 'deleted_at'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
