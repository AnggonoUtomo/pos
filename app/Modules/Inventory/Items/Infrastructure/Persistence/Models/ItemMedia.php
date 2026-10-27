<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ItemMedia extends Media
{
    use HasUlids;
}
