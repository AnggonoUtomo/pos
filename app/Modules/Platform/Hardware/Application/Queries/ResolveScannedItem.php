<?php

namespace App\Modules\Platform\Hardware\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use InvalidArgumentException;

class ResolveScannedItem
{
    public function __construct(private SaleItemCatalog $catalog) {}

    /** @return array{item_id: string, sku: string, name: string, unit_id: string, unit_name: string, barcode: ?string}|null */
    public function handle(string $code): ?array
    {
        $code = trim($code);
        if (! preg_match('/^[\x20-\x7E]{1,64}$/D', $code)) {
            throw new InvalidArgumentException('Kode scan tidak valid.');
        }
        $match = $this->catalog->scan($code);
        if ($match === null) {
            return null;
        }
        foreach ($match->item->units as $unit) {
            if ($unit->id === $match->unitId) {
                return ['item_id' => $match->item->id, 'sku' => $match->item->sku, 'name' => $match->item->name,
                    'unit_id' => $unit->id, 'unit_name' => $unit->name, 'barcode' => $unit->barcode];
            }
        }

        return null;
    }
}
