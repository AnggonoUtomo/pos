<?php

namespace App\Modules\Inventory\Warehouses\Application\Actions;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateWarehouse
{
    public function __construct(private WarehouseRepository $warehouses) {}

    public function handle(CreateWarehouseData $data): string
    {
        $values = Validator::make([
            'code' => trim($data->code),
            'name' => trim($data->name),
        ], [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:150'],
        ])->validate();
        $normalized = new CreateWarehouseData($values['code'], $values['name'], $data->isActive);

        return DB::transaction(fn (): string => $this->warehouses->create($normalized));
    }
}
