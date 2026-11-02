<?php

namespace App\Modules\Inventory\Items\Application\Enums;

enum ClassificationKind: string
{
    case Category = 'categories';
    case Brand = 'brands';

    public function field(): string
    {
        return $this === self::Category ? 'category_id' : 'brand_id';
    }
}
