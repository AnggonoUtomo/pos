<?php

namespace App\Modules\Inventory\Units\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemUnitUsage;
use App\Modules\Inventory\Units\Application\Contracts\UnitRepository;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ManageUnit
{
    public function __construct(private UnitRepository $repository, private ItemUnitUsage $usage) {}

    public function update(string $id, CreateUnitData $data): void
    {
        $input = ['code' => trim($data->code), 'name' => trim($data->name), 'symbol' => trim($data->symbol)];
        Validator::make($input, [
            'code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
            'symbol' => ['required', 'string', 'max:16'],
        ])->validate();
        DB::transaction(function () use ($id, $input, $data): void {
            $this->repository->lock($id);
            if (! $data->isActive) {
                $this->assertUnused($id);
            }
            $this->repository->update($id, new CreateUnitData($input['code'], $input['name'], $input['symbol'], $data->isActive));
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lock($id);
            $this->assertUnused($id);
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

    private function assertUnused(string $id): void
    {
        if ($this->usage->isReferenced($id)) {
            throw new InvalidArgumentException('Satuan sudah digunakan barang dan tidak boleh dihapus atau dinonaktifkan.');
        }
    }
}
