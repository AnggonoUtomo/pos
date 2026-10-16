<?php

namespace App\Modules\Sales\Parties\Application\Contracts;

interface ItemPriceRepository
{
    public function activeLevels(bool $lock): array;

    public function replace(string $itemId, array $prices): void;

    public function forItems(array $itemIds, ?string $customerLevelId = null, bool $lock = false): array;
}
