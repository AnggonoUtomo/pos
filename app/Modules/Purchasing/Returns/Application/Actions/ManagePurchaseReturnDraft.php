<?php

namespace App\Modules\Purchasing\Returns\Application\Actions;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\ReturnQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class ManagePurchaseReturnDraft
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnPurchaseLookup $purchases) {}

    public function save(array $input, ?string $id = null): string
    {
        $input = self::normalize($input);
        $data = Validator::make($input, [
            'purchase_order_id' => ['required', 'ulid'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:purchase_order_line_id,quantity'],
            'lines.*.purchase_order_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
            'lines.*.quantity' => ['required', 'string', 'max:21', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'version' => [$id === null ? 'sometimes' : 'required', 'integer', 'min:1', 'max:2147483646'],
        ])->validate();

        return DB::transaction(function () use ($data, $input, $id): string {
            if ($id !== null) {
                $draft = $this->drafts->lock($id);
                $this->assertEditable($draft, $input['version'] ?? null);
                if ($draft['purchase_order_id'] !== $data['purchase_order_id']) {
                    throw ValidationException::withMessages(['purchase_order_id' => 'Pembelian asal draft tidak boleh diganti.']);
                }
            }

            $purchase = $this->purchases->findEligible($data['purchase_order_id']);
            if ($purchase === null) {
                throw ValidationException::withMessages(['purchase_order_id' => 'Pembelian diterima tidak ditemukan.']);
            }

            $sourceLines = array_column($purchase->lines, null, 'id');
            $lines = [];
            foreach ($data['lines'] as $index => $line) {
                $source = $sourceLines[$line['purchase_order_line_id']] ?? null;
                if ($source === null) {
                    throw ValidationException::withMessages(["lines.{$index}.purchase_order_line_id" => 'Baris bukan bagian dari pembelian asal.']);
                }
                try {
                    $quantity = ReturnQuantity::from($line['quantity'], $source['quantity'], $source['conversion_ratio']);
                } catch (InvalidArgumentException $error) {
                    throw ValidationException::withMessages(["lines.{$index}.quantity" => $error->getMessage()]);
                }
                $lines[] = ['purchase_order_line_id' => $source['id'], 'quantity' => $quantity->quantity,
                    'base_quantity' => $quantity->baseQuantity, 'snapshot' => $source];
            }
            usort($lines, fn ($left, $right) => strcmp($left['purchase_order_line_id'], $right['purchase_order_line_id']));
            $notes = trim($data['notes'] ?? '');
            $header = ['purchase_order_id' => $purchase->header['id'], 'purchase_order_number' => $purchase->header['number'],
                'supplier_name' => $purchase->header['supplier_name'], 'snapshot' => $purchase->header,
                'notes' => $notes === '' ? null : $notes];

            return $this->drafts->save($header, $lines, $id);
        }, 3);
    }

    public static function normalize(array $input): array
    {
        foreach (['purchase_order_id'] as $key) {
            if (is_string($input[$key] ?? null)) {
                $input[$key] = strtolower($input[$key]);
            }
        }
        if (is_array($input['lines'] ?? null)) {
            foreach ($input['lines'] as &$line) {
                if (is_array($line) && is_string($line['purchase_order_line_id'] ?? null)) {
                    $line['purchase_order_line_id'] = strtolower($line['purchase_order_line_id']);
                }
            }
            unset($line);
        }

        return $input;
    }

    private function assertEditable(array $draft, mixed $version): void
    {
        $validated = Validator::make(['version' => $version], ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        if ($draft['status'] !== 'draft') {
            throw ValidationException::withMessages(['purchase_return' => 'Hanya draft retur yang boleh diubah.']);
        }
        if ((int) $draft['version'] !== (int) $validated['version']) {
            throw ValidationException::withMessages(['version' => 'Draft retur telah berubah. Muat ulang sebelum melanjutkan.']);
        }
    }
}
