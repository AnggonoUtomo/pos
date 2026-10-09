<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ItemUnit extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['item_id', 'unit_id', 'conversion_ratio', 'barcode', 'is_sale_unit', 'is_purchase_unit'];

    protected function casts(): array
    {
        return ['conversion_ratio' => 'decimal:6', 'is_sale_unit' => 'boolean', 'is_purchase_unit' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventory.item_units')
            ->logOnly(['item_id', 'unit_id', 'conversion_ratio', 'barcode', 'is_sale_unit', 'is_purchase_unit', 'deleted_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
