<?php

namespace App\Modules\Sales\Parties\Application\Actions;

use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageCustomer
{
    public function __construct(private PartiesRepository $repository) {}

    public function save(CustomerData $data, ?string $id = null): string
    {
        $input = ['code' => strtoupper(trim($data->code)), 'name' => trim($data->name),
            'customer_level_id' => strtolower($data->levelId), 'is_active' => $data->isActive];
        foreach (['phone', 'email', 'address'] as $field) {
            $input[$field] = $data->$field === null || trim($data->$field) === '' ? null : trim($data->$field);
        }
        Validator::make($input, [
            'code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
            'customer_level_id' => ['required', 'ulid'], 'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($input, $id): string {
            $this->assertActiveLevel($input['customer_level_id']);
            if ($id !== null) {
                $this->repository->lockCustomer($id);
            }

            return $this->repository->saveCustomer($input, $id);
        }, 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockCustomer($id);
            $this->repository->deleteCustomer($id);
        }, 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $levelId = $this->repository->customerLevelId($id, deleted: true);
            $this->assertActiveLevel($levelId);
            $customer = $this->repository->lockCustomer($id, deleted: true);
            if ($customer['customer_level_id'] !== $levelId) {
                throw ValidationException::withMessages(['customer_level_id' => 'Level berubah. Muat ulang data pelanggan.']);
            }
            $this->repository->restoreCustomer($id);
        }, 3);
    }

    private function assertActiveLevel(string $id): void
    {
        try {
            $level = $this->repository->lockLevel($id);
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['customer_level_id' => 'Pilih level pelanggan aktif yang tersedia.']);
        }
        if (! $level['is_active']) {
            throw ValidationException::withMessages(['customer_level_id' => 'Pilih level pelanggan aktif yang tersedia.']);
        }
    }
}
