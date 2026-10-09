<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Domain\ValueObjects\ConversionRatio;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateItem
{
    public function __construct(private ItemRepository $items, private UnitLookup $units) {}

    public function handle(CreateItemData $data): string
    {
        $sku = $this->text($data->sku, 64);
        $name = $this->text($data->name, 150);
        $units = [];

        foreach ($data->units as $unit) {
            if (! $unit instanceof ItemUnitData || isset($units[$unit->unitId])) {
                throw new InvalidArgumentException('Satuan barang harus valid dan tidak boleh berulang.');
            }

            $ratio = ConversionRatio::fromString($unit->conversionRatio);
            if ($unit->unitId === $data->baseUnitId) {
                $ratio->assertBaseUnit();
            }

            $barcode = $unit->barcode === null ? null : trim($unit->barcode);
            if ($barcode !== null && mb_strlen($barcode) > 64) {
                throw new InvalidArgumentException('Barcode maksimal 64 karakter.');
            }

            $units[$unit->unitId] = new ItemUnitData($unit->unitId, $ratio->value(), $barcode === '' ? null : $barcode, $unit->isSaleUnit, $unit->isPurchaseUnit);
        }

        $units[$data->baseUnitId] ??= new ItemUnitData($data->baseUnitId, '1.000000');
        // Consistent lock order reduces deadlocks when items share multiple units.
        ksort($units);
        $normalized = new CreateItemData($sku, $name, $data->baseUnitId, array_values($units), $data->isActive);

        return DB::transaction(function () use ($normalized): string {
            foreach ($normalized->units as $unit) {
                if ($this->units->findActiveById($unit->unitId, lock: true) === null) {
                    throw new InvalidArgumentException('Satuan harus tersedia, aktif, dan tidak dihapus.');
                }
            }

            return $this->items->create($normalized);
        });
    }

    private function text(string $value, int $maximum): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $maximum) {
            throw new InvalidArgumentException("Nilai wajib diisi dengan maksimal {$maximum} karakter.");
        }

        return $value;
    }
}
