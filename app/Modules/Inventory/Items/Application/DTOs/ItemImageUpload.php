<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemImageUpload
{
    public function __construct(public string $temporaryPath, public string $extension) {}
}
