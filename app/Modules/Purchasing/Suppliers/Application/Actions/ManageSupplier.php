<?php

namespace App\Modules\Purchasing\Suppliers\Application\Actions;

use App\Modules\Purchasing\Suppliers\Application\Contracts\SuppliersRepository;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ManageSupplier
{
    public function __construct(private SuppliersRepository $repository) {}

    public function save(SupplierData $input, ?string $id = null): string
    {
        $data = ['code' => mb_strtoupper(trim($input->code)), 'name' => trim($input->name),
            'phone' => $this->optional($input->phone), 'email' => $this->optional($input->email),
            'address' => $this->optional($input->address), 'is_active' => $input->isActive];
        Validator::make($data, [
            'code' => ['required', 'string', 'max:32', Rule::unique('suppliers', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ], ['code.unique' => 'Kode sudah digunakan, termasuk pada data terhapus.'])->validate();

        return DB::transaction(fn () => $this->repository->save($data, $id), 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(fn () => $this->repository->delete($id), 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(fn () => $this->repository->restore($id), 3);
    }

    private function optional(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
