<?php

namespace App\Modules\Inventory\Items\Infrastructure\Support;

use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver;
use Throwable;

class ItemMediaObserver extends MediaObserver
{
    public function deleted(Media $media): void
    {
        // Keep the old file until its database deletion is committed.
        $remove = function () use ($media): void {
            try {
                parent::deleted($media);
            } catch (Throwable) {
                Log::warning('Item image file cleanup failed.', ['media_id' => $media->getKey()]);
            }
        };
        if ($media->getConnection()->transactionLevel() > 0) {
            $media->getConnection()->afterCommit($remove);
        } else {
            $remove();
        }
    }
}
