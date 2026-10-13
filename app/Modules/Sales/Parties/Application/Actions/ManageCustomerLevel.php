<?php

namespace App\Modules\Sales\Parties\Application\Actions;

use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ManageCustomerLevel
{
    public function __construct(private PartiesRepository $repository) {}

    public function save(CustomerLevelData $data, ?string $id = null): string
    {
        $input = ['code' => strtoupper(trim($data->code)), 'name' => trim($data->name), 'is_active' => $data->isActive];
        Validator::make($input, ['code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150']])->validate();

        return DB::transaction(function () use ($input, $id): string {
            if ($id !== null) {
                $this->repository->lockLevel($id);
                if (! $input['is_active']) {
                    $this->assertUnused($id);
                }
            }

            return $this->repository->saveLevel($input, $id);
        }, 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockLevel($id);
            $this->assertUnused($id);
            $this->repository->deleteLevel($id);
        }, 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockLevel($id, deleted: true);
            $this->repository->restoreLevel($id);
        }, 3);
    }

    private function assertUnused(string $id): void
    {
        if ($this->repository->isLevelReferenced($id)) {
            throw new InvalidArgumentException('Level sudah digunakan pelanggan atau harga barang dan tidak boleh dihapus atau dinonaktifkan.');
        }
    }
}
