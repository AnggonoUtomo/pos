<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemImageStore;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageUpload;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaveItemCatalog
{
    public function __construct(private CreateItem $create, private ManageItem $manage, private ItemPriceCatalog $prices, private ItemImageStore $images) {}

    public function save(CreateItemData $data, array $prices, ?string $id = null, ?ItemImageUpload $image = null, bool $removeImage = false): string
    {
        if ($image !== null && $removeImage) {
            throw ValidationException::withMessages(['image' => 'Pilih gambar baru atau hapus gambar, bukan keduanya.']);
        }
        if (($image !== null || $removeImage) && DB::transactionLevel() > 0) {
            throw new \LogicException('Mutation gambar harus memiliki transaction terluar.');
        }
        $mediaId = $image === null ? null : (string) Str::ulid();
        try {
            return DB::transaction(function () use ($data, $prices, $id, $image, $removeImage, $mediaId): string {
                $this->prices->activeLevels(lock: true);
                if ($id === null) {
                    $id = $this->create->handle($data);
                } else {
                    $this->manage->update($id, $data);
                }
                $this->prices->saveForItem($id, $prices);
                if ($image !== null) {
                    $this->images->replace($id, $image, $mediaId);
                } elseif ($removeImage) {
                    $this->images->remove($id);
                }

                return $id;
            }, $image === null && ! $removeImage ? 3 : 1);
        } catch (Throwable $exception) {
            if ($mediaId !== null) {
                $this->images->discardUncommitted($mediaId);
            }
            throw $exception;
        }
    }
}
