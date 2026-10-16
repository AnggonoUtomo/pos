<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Domain\ValueObjects\LineAmounts;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CheckCart
{
    public function __construct(private SaleContext $context, private SaleItemCatalog $items,
        private ItemPriceCatalog $prices, private StockAvailability $stock) {}

    /** @param list<CartLineData> $lines */
    public function handle(string $warehouseId, string $customerId, array $lines, bool $lock = false): array
    {
        if (! array_is_list($lines) || count($lines) < 1 || count($lines) > 100) {
            throw ValidationException::withMessages(['lines' => 'Keranjang harus berisi 1 sampai 100 baris.']);
        }
        foreach ($lines as $line) {
            if (! $line instanceof CartLineData) {
                throw ValidationException::withMessages(['lines' => 'Baris keranjang tidak valid.']);
            }
        }

        return DB::transaction(function () use ($warehouseId, $customerId, $lines, $lock): array {
            if ($lock) {
                $this->prices->activeLevels(lock: true);
            }
            $context = $this->context->resolve($warehouseId, $customerId, $lock);
            $ids = array_values(array_unique(array_map(fn ($line) => $line->itemId, $lines)));
            $items = $this->items->findMany($ids, $lock);
            $prices = $this->prices->forItems($ids, $context['customer']->levelId, $lock);
            $stock = $this->stock->forItems($warehouseId, $ids, $lock);
            $seen = $required = $rows = [];
            $total = BigDecimal::zero()->toScale(2);
            foreach ($lines as $index => $line) {
                $item = $items[$line->itemId] ?? null;
                $unit = $item === null ? null : collect($item->units)->firstWhere('id', $line->unitId);
                if ($unit === null) {
                    throw ValidationException::withMessages(["lines.{$index}.unit_id" => 'Barang atau satuan jual tidak tersedia.']);
                }
                $key = $line->itemId.':'.$line->unitId;
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(["lines.{$index}.unit_id" => 'Pasangan barang dan satuan tidak boleh berulang.']);
                }
                $seen[$key] = true;
                $price = collect($prices[$line->itemId] ?? [])->firstWhere('unitId', $line->unitId)?->amount;
                if ($price === null) {
                    throw ValidationException::withMessages(["lines.{$index}.unit_id" => 'Harga satuan untuk level pelanggan ini belum tersedia.']);
                }
                try {
                    $amounts = LineAmounts::calculate($line->quantity, $unit->ratio, $price);
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(["lines.{$index}.quantity" => $e->getMessage()]);
                }
                $required[$item->id] = ($required[$item->id] ?? BigDecimal::zero())->plus($amounts->baseQuantity);
                if ($required[$item->id]->isGreaterThan($stock[$item->id])) {
                    throw ValidationException::withMessages(["lines.{$index}.quantity" => 'Stok gudang tidak mencukupi untuk seluruh satuan barang ini.']);
                }
                $total = $total->plus($amounts->subtotal);
                if ($total->isGreaterThan('9999999999999999999999999999.99')) {
                    throw ValidationException::withMessages(['lines' => 'Nilai keranjang melebihi batas.']);
                }
                $rows[] = ['item_id' => $item->id, 'unit_id' => $unit->id, 'sku' => $item->sku, 'name' => $item->name,
                    'base_unit_id' => $item->baseUnitId, 'base_unit_name' => $item->baseUnitName,
                    'unit_name' => $unit->name, 'quantity' => $amounts->quantity, 'base_quantity' => $amounts->baseQuantity,
                    'conversion_ratio' => $unit->ratio, 'unit_price' => $price, 'subtotal' => $amounts->subtotal];
            }

            $canonical = $rows;
            usort($canonical, fn ($a, $b) => [$a['item_id'], $a['unit_id']] <=> [$b['item_id'], $b['unit_id']]);
            $fingerprint = hash('sha256', json_encode([$context, $canonical, (string) $total], JSON_THROW_ON_ERROR));

            return ['lines' => $rows, 'subtotal' => (string) $total, 'customer' => $context['customer'], 'warehouse' => $context['warehouse'],
                'fingerprint' => $fingerprint, 'checked_at' => now()->toIso8601String()];
        });
    }
}
