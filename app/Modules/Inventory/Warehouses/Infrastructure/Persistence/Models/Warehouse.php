<?php

namespace App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Warehouse extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventory.warehouses')
            ->logOnly(['code', 'name', 'is_active', 'deleted_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
