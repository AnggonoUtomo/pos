<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Domain\ValueObjects\ConversionRatio;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseCostLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PreviewItemPrices
{
    public function __construct(private PurchaseCostLookup $costs, private ItemStockLookup $items,
        private UnitLookup $units, private ItemPriceCatalog $prices) {}

    public function handle(string $itemId, array $input): array
    {
        $data = Validator::make($input, ['units' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'units.*.unit_id' => ['required', 'ulid', 'distinct:ignore_case'],
            'units.*.conversion_ratio' => ['required', 'string', 'max:21'],
            'markups' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'markups.*.customer_level_id' => ['required', 'ulid', 'distinct:ignore_case'],
            'markups.*.percent' => ['required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,2})?$/D'],
        ])->validate();
        if (count($data['units']) * count($data['markups']) > 5000) {
            throw ValidationException::withMessages(['prices' => 'Usulan maksimal 5.000 kombinasi.']);
        }
        $item = $this->items->findActive(strtolower($itemId)) ?? throw ValidationException::withMessages(['item' => 'Barang harus aktif dan tersedia.']);
        $levels = array_column($this->prices->activeLevels(), null, 'id');
        foreach ($data['markups'] as &$markup) {
            $markup['customer_level_id'] = strtolower($markup['customer_level_id']);
            if (! isset($levels[$markup['customer_level_id']])) {
                throw ValidationException::withMessages(['markups' => 'Level tidak aktif atau sudah berubah. Muat ulang form.']);
            }
        }
        unset($markup);
        $activeUnits = $this->units->findManyActive(array_map('strtolower', array_column($data['units'], 'unit_id')));
        foreach ($data['units'] as &$unit) {
            $unit['unit_id'] = strtolower($unit['unit_id']);
            if (! isset($activeUnits[$unit['unit_id']])) {
                throw ValidationException::withMessages(['units' => 'Satuan harus aktif.']);
            }
            try {
                $ratio = ConversionRatio::fromString($unit['conversion_ratio']);
                if ($unit['unit_id'] === $item->baseUnitId) {
                    $ratio->assertBaseUnit();
                }
                $unit['conversion_ratio'] = $ratio->value();
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['units' => $error->getMessage()]);
            }
        }
        unset($unit);
        $cost = $this->costs->latestForItem($item->id);
        if ($cost === null) {
            return ['reference' => null, 'prices' => []];
        }
        if ($cost->baseUnitId !== $item->baseUnitId) {
            throw ValidationException::withMessages(['item' => 'Satuan dasar biaya acuan tidak sesuai.']);
        }
        $rows = [];
        try {
            foreach ($data['units'] as $unit) {
                foreach ($data['markups'] as $markup) {
                    $rows[] = ['unit_id' => $unit['unit_id'], 'customer_level_id' => $markup['customer_level_id'],
                        'amount' => $this->prices->markup($cost->unitCost, $cost->conversionRatio, $unit['conversion_ratio'], $markup['percent'])];
                }
            }
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['prices' => $error->getMessage()]);
        }

        return ['reference' => ['number' => $cost->number, 'received_at' => $cost->receivedAt,
            'unit_name' => $cost->unitName, 'unit_cost' => $cost->unitCost, 'conversion_ratio' => $cost->conversionRatio], 'prices' => $rows];
    }
}
