<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemImageStore;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageFile;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageUpload;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Throwable;

class MediaLibraryItemImageStore implements ItemImageStore
{
    public function replace(string $itemId, ItemImageUpload $image, string $mediaId): void
    {
        $item = $this->lock($itemId);
        try {
            $item->addMedia($image->temporaryPath)->preservingOriginal()
                ->usingName('Gambar barang')->usingFileName($mediaId.'.'.$image->extension)
                ->withProperties(['id' => $mediaId])->toMediaCollection('item-image', 'local');
        } catch (FileCannotBeAdded) {
            throw ValidationException::withMessages(['image' => 'Gambar gagal disimpan. Periksa file dan kapasitas penyimpanan.']);
        }
        activity('inventory.items')->performedOn($item)->event('image.updated')
            ->withProperties(['media_id' => $mediaId])->log('Gambar utama barang diperbarui.');
    }

    public function remove(string $itemId): void
    {
        $item = $this->lock($itemId);
        if (! $item->hasMedia('item-image')) {
            return;
        }
        $item->clearMediaCollection('item-image');
        activity('inventory.items')->performedOn($item)->event('image.removed')->log('Gambar utama barang dihapus.');
    }

    private function lock(string $itemId): Item
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Mutation gambar memerlukan transaction default.');
        }

        return Item::whereKey($itemId)->lockForUpdate()->firstOrFail();
    }

    public function discardUncommitted(string $mediaId): void
    {
        if (! Str::isUlid($mediaId) || ItemMedia::whereKey($mediaId)->exists()) {
            return;
        }
        try {
            Storage::disk('local')->deleteDirectory('item-images/'.$mediaId);
        } catch (Throwable) {
            Log::warning('Uncommitted item image cleanup failed.', ['media_id' => $mediaId]);
        }
    }

    public function find(string $itemId, string $mediaId): ?ItemImageFile
    {
        $item = Item::find($itemId);
        $media = $item?->media()->whereKey($mediaId)->where('collection_name', 'item-image')->first();
        if ($media === null || $media->disk !== 'local' || ! is_file($media->getPath())) {
            return null;
        }

        return new ItemImageFile($media->getPath(), $media->mime_type);
    }
}
