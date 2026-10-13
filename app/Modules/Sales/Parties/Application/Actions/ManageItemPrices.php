<?php

namespace App\Modules\Sales\Parties\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemPricingLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceRepository;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Domain\ValueObjects\PriceAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class ManageItemPrices implements ItemPriceCatalog
{
    public function __construct(private ItemPriceRepository $prices, private ItemPricingLookup $items) {}

    public function activeLevels(bool $lock = false): array
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Level lock memerlukan transaction.');
        }
        $levels = $this->prices->activeLevels($lock);
        if (count($levels) > 1000) {
            throw ValidationException::withMessages(['prices' => 'Jumlah level melebihi batas editor harga.']);
        }

        return $levels;
    }

    public function saveForItem(string $itemId, array $prices): void
    {
        DB::transaction(function () use ($itemId, $prices): void {
            $levels = $this->activeLevels(lock: true);
            $item = $this->items->lock($itemId);
            if ($item === null) {
                throw ValidationException::withMessages(['prices' => 'Konfigurasi barang/satuan berubah. Muat ulang form.']);
            }
            if ($item->saleUnitIds !== [] && $levels === []) {
                throw ValidationException::withMessages(['prices' => 'Belum ada level pelanggan aktif.']);
            }
            $expected = [];
            foreach ($item->saleUnitIds as $unit) {
                foreach ($levels as $level) {
                    $expected[] = $unit.':'.$level->id;
                }
            }
            $seen = [];
            $normalized = [];
            foreach ($prices as $index => $data) {
                if (! $data instanceof ItemPriceData) {
                    throw ValidationException::withMessages(['prices' => 'Data harga tidak valid.']);
                }
                $key = strtolower($data->unitId).':'.strtolower($data->customerLevelId);
                if (! in_array($key, $expected, true) || isset($seen[$key])) {
                    throw ValidationException::withMessages(['prices' => 'Pasangan satuan/level harga tidak valid atau berulang.']);
                }
                $seen[$key] = true;
                try {
                    $amount = PriceAmount::fromString($data->amount)->value;
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages(["prices.{$index}.amount" => $exception->getMessage()]);
                }
                $normalized[] = new ItemPriceData(strtolower($data->unitId), strtolower($data->customerLevelId), $amount);
            }
            if (count($seen) !== count($expected)) {
                throw ValidationException::withMessages(['prices' => 'Isi harga untuk setiap satuan jual dan seluruh level pelanggan aktif.']);
            }
            $this->prices->replace($itemId, $normalized);
        }, 3);
    }

    public function forItems(array $itemIds): array
    {
        return $this->prices->forItems($itemIds);
    }
}
