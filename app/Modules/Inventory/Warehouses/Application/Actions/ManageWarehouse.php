<?php

namespace App\Modules\Inventory\Warehouses\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\StockUsage;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ManageWarehouse
{
    public function __construct(private WarehouseRepository $repository, private StockUsage $stock) {}

    public function update(string $id, CreateWarehouseData $data): void
    {
        $input = ['code' => trim($data->code), 'name' => trim($data->name)];
        Validator::make($input, [
            'code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
        ])->validate();
        DB::transaction(function () use ($id, $input, $data): void {
            $this->repository->lock($id);
            if (! $data->isActive && $this->stock->hasWarehouseHistory($id)) {
                throw new InvalidArgumentException('Gudang berhistori stok tidak boleh dinonaktifkan.');
            }
            $this->repository->update($id, new CreateWarehouseData($input['code'], $input['name'], $data->isActive));
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lock($id);
            if ($this->stock->hasWarehouseHistory($id)) {
                throw new InvalidArgumentException('Gudang berhistori stok tidak boleh dihapus.');
            }
            $this->repository->delete($id);
        });
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lock($id, deleted: true);
            $this->repository->restore($id);
        });
    }
}
