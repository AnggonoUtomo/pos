<?php

namespace App\Modules\Finance\Payments\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagePaymentMethod
{
    public function __construct(private PaymentsRepository $repository) {}

    public function save(PaymentMethodData $data, ?string $id = null): string
    {
        $input = ['code' => strtoupper(trim($data->code)), 'name' => trim($data->name), 'type' => $data->type,
            'payment_account_id' => strtolower($data->accountId), 'is_active' => $data->isActive];
        Validator::make($input, ['code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(PaymentMethodType::class)], 'payment_account_id' => ['required', 'ulid']])->validate();

        return DB::transaction(function () use ($input, $id): string {
            $this->assertAccount($input['payment_account_id'], $input['type']);
            if ($id !== null) {
                $current = $this->repository->lockMethod($id);
                if (! $input['is_active'] || $input['type'] !== $current['type'] || $input['payment_account_id'] !== $current['payment_account_id']) {
                    $this->assertNoHistory($id);
                }
            }

            return $this->repository->saveMethod($input, $id);
        }, 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->lockMethod($id);
            $this->assertNoHistory($id);
            $this->repository->deleteMethod($id);
        }, 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $accountId = $this->repository->methodAccountId($id, deleted: true);
            $account = $this->lockActiveAccount($accountId);
            $method = $this->repository->lockMethod($id, deleted: true);
            if ($method['payment_account_id'] !== $accountId || $account['type'] !== PaymentMethodType::from($method['type'])->accountType()->value) {
                throw ValidationException::withMessages(['payment_account_id' => 'Relasi akun berubah atau jenis tidak cocok. Muat ulang data.']);
            }
            $this->repository->restoreMethod($id);
        }, 3);
    }

    private function assertAccount(string $id, string $type): void
    {
        $account = $this->lockActiveAccount($id);
        if ($account['type'] !== PaymentMethodType::from($type)->accountType()->value) {
            throw ValidationException::withMessages(['payment_account_id' => 'Jenis akun tidak cocok dengan metode pembayaran.']);
        }
    }

    private function assertNoHistory(string $id): void
    {
        if ($this->repository->hasMethodHistory($id)) {
            throw ValidationException::withMessages(['payments' => 'Metode memiliki histori pembayaran dan tidak boleh dihapus, dinonaktifkan, diganti jenis atau dipindah akun.']);
        }
    }

    private function lockActiveAccount(string $id): array
    {
        try {
            $account = $this->repository->lockAccount($id);
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['payment_account_id' => 'Pilih akun aktif yang tersedia.']);
        }
        if (! $account['is_active']) {
            throw ValidationException::withMessages(['payment_account_id' => 'Pilih akun aktif yang tersedia.']);
        }

        return $account;
    }
}
