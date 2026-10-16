<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Actions;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManagePurchaseOrder
{
    public function __construct(private PurchaseOrderRepository $repository, private PreparePurchaseOrder $prepare) {}

    public function save(array $input, ?string $id = null, ?string $demoKey = null): string
    {
        return DB::transaction(function () use ($input, $id, $demoKey): string {
            if ($id !== null) {
                $this->assertEditable($this->repository->lock($id), $input['version'] ?? null);
            }
            [$header, $lines] = $this->prepare->handle($input);
            if ($id === null && $demoKey !== null) {
                Validator::make(['demo_key' => $demoKey], ['demo_key' => ['required', 'string', 'max:64']])->validate();
                $header['demo_key'] = $demoKey;
            }

            return $this->repository->save($header, $lines, $id);
        }, 3);
    }

    public function delete(string $id, mixed $version): void
    {
        DB::transaction(function () use ($id, $version): void {
            $this->assertEditable($this->repository->lock($id), $version);
            $this->repository->delete($id);
        }, 3);
    }

    public function restore(string $id, mixed $version): void
    {
        DB::transaction(function () use ($id, $version): void {
            $this->assertEditable($this->repository->lock($id, deleted: true), $version);
            $this->repository->restore($id);
        }, 3);
    }

    private function assertEditable(array $record, mixed $version): void
    {
        Validator::make(['version' => $version], ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        if ($record['status'] !== 'draft') {
            throw ValidationException::withMessages(['purchase_order' => 'Hanya draft pembelian yang boleh diubah.']);
        }
        if ($record['version'] !== (int) $version) {
            throw ValidationException::withMessages(['purchase_order' => 'Draft sudah berubah. Muat ulang halaman sebelum melanjutkan.']);
        }
    }
}
