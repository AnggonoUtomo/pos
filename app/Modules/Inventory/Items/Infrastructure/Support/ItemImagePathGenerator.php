<?php

namespace App\Modules\Inventory\Items\Infrastructure\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class ItemImagePathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return 'item-images/'.$media->getKey().'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}
