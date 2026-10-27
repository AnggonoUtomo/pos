<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemImageFile
{
    public function __construct(public string $path, public string $mimeType) {}
}
