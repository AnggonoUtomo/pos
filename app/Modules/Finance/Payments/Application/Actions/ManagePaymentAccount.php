<?php

namespace App\Modules\Finance\Payments\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Domain\Enums\PaymentAccountType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagePaymentAccount
{
    public function __construct(private PaymentsRepository $repository) {}

    public function save(PaymentAccountData $data, ?string $id = null): string
    {
        $input = ['code' => strtoupper(trim($data->code)), 'name' => trim($data->name), 'type' => $data->type, 'is_active' => $data->isActive];
        foreach (['bank_name' => $data->bankName, 'account_number' => $data->accountNumber, 'account_holder' => $data->accountHolder] as $field => $value) {
            $input[$field] = $value === null || trim($value) === '' ? null : trim($value);
        }
        Validator::make($input, ['code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::enum(PaymentAccountType::class)],
            'bank_name' => ['nullable', 'string', 'max:150', 'prohibited_unless:type,bank'], 'account_number' => ['nullable', 'string', 'max:64', 'prohibited_unless:type,bank'],
            'account_holder' => ['nullable', 'string', 'max:150', 'prohibited_unless:type,bank']])->validate();

        return DB::transaction(function () use ($input, $id): string {
            if ($id !== null) {
                $existing = $this->repository->lockAccount($id);
                if (! $input['is_active'] || $existing['type'] !== $input['type']) {
                    $this->assertUnused($id);
                }
            }

            return $this->repository->saveAccount($input, $id);
        }, 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockAccount($id);
            $this->assertUnused($id);
            $this->repository->deleteAccount($id);
        }, 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockAccount($id, deleted: true);
            $this->repository->restoreAccount($id);
        }, 3);
    }

    private function assertUnused(string $id): void
    {
        if ($this->repository->isAccountReferenced($id)) {
            throw ValidationException::withMessages(['payments' => 'Akun sudah digunakan metode pembayaran dan tidak boleh dihapus, dinonaktifkan, atau diganti jenisnya.']);
        }
    }
}
