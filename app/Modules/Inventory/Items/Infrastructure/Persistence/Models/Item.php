<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Item extends Model implements HasMedia
{
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['sku', 'name', 'base_unit_id', 'is_active'];

    protected $hidden = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('item-image')->useDisk('local')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function imageUrl(): ?string
    {
        if ($this->trashed()) {
            return null;
        }
        $image = $this->getFirstMedia('item-image');

        return $image === null ? null : route('inventory.items.image', ['id' => $this->id, 'mediaId' => $image->id]);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventory.items')
            ->logOnly(['sku', 'name', 'base_unit_id', 'is_active', 'deleted_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
