<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

interface ItemClassificationLookup
{
    /** @return array{categories: list<array{id:string,code:string,name:string}>, brands: list<array{id:string,code:string,name:string}>} */
    public function choices(): array;
}
