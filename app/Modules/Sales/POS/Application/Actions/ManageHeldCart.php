<?php

namespace App\Modules\Sales\POS\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Sales\POS\Application\Contracts\HeldCartRepository;
use App\Modules\Sales\POS\Application\Queries\SaleContext;
use App\Modules\Sales\POS\Domain\ValueObjects\InvoiceAmounts;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ManageHeldCart
{
    public function __construct(private HeldCartRepository $carts, private SaleContext $context, private SaleItemCatalog $items) {}

    public function save(string $id, array $input, int $actorId): array
    {
        $data = $this->normalize($id, $input, $actorId);
        $id = strtolower($id);
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        // Resolve master data before locking the draft: checkout locks warehouse before draft.
        $context = $this->context->resolve($data['warehouse_id'], $data['customer_id']);
        $items = $this->items->findMany(array_values(array_unique(array_column($data['lines'], 'item_id'))));
        $snapshots = [];
        foreach ($data['lines'] as $index => $line) {
            $item = $items[$line['item_id']] ?? null;
            $unit = $item === null ? null : collect($item->units)->firstWhere('id', $line['unit_id']);
            if ($unit === null) {
                throw ValidationException::withMessages(["lines.{$index}.unit_id" => 'Barang/satuan jual tidak tersedia.']);
            }
            $snapshots[] = ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->name,
                'base_unit_id' => $item->baseUnitId, 'base_unit_name' => $item->baseUnitName, 'stock' => '0',
                'image_url' => null, 'units' => [['id' => $unit->id, 'code' => $unit->code, 'name' => $unit->name,
                    'ratio' => $unit->ratio, 'barcode' => null, 'price' => null]]];
        }
        $write = function () use ($id, $data, $actorId, $hash, $context, $snapshots): array {
            $existing = $this->carts->findOwned($id, $actorId, lock: true);
            if ($existing !== null) {
                $this->assertOpen($existing);
                if (hash_equals($existing['request_hash'], $hash)) {
                    return $this->reference($existing);
                }
                if ($existing['version'] !== $data['version']) {
                    $this->stale();
                }
            } elseif ($data['version'] !== 0) {
                abort(404);
            }
            $row = $this->carts->write($id, ['actor_id' => $actorId, 'version' => ($existing['version'] ?? 0) + 1,
                'label' => $data['label'], 'warehouse_id' => $data['warehouse_id'], 'customer_id' => $data['customer_id'],
                'warehouse_name' => $context['warehouse']->name, 'customer_name' => $context['customer']->name,
                'payload' => array_diff_key($data, ['label' => true, 'version' => true]),
                'line_snapshots' => $snapshots, 'request_hash' => $hash], create: $existing === null);
            $this->carts->audit($id, $actorId, $existing === null ? 'held' : 'updated');

            return $this->reference($row);
        };
        try {
            return DB::transaction($write, 3);
        } catch (UniqueConstraintViolationException) {
            return DB::transaction(function () use ($id, $actorId, $hash): array {
                $row = $this->carts->findOwned($id, $actorId, lock: true);
                abort_if($row === null, 404);
                $this->assertOpen($row);
                if (! hash_equals($row['request_hash'], $hash)) {
                    $this->stale();
                }

                return $this->reference($row);
            });
        }
    }

    public function cancel(string $id, int $version, int $actorId): void
    {
        DB::transaction(function () use ($id, $version, $actorId): void {
            $row = $this->carts->findOwned(strtolower($id), $actorId, lock: true);
            abort_if($row === null, 404);
            if ($row['deleted_at'] !== null && $row['version'] === $version + 1) {
                return;
            }
            $this->assertOpen($row);
            if ($row['version'] !== $version) {
                $this->stale();
            }
            $this->carts->cancel($row['id']);
            $this->carts->audit($row['id'], $actorId, 'cancelled');
        }, 3);
    }

    public function checkoutSource(array $source, int $actorId): array
    {
        $row = $this->carts->findOwned($source['id'], $actorId, lock: true);
        if ($row === null || $row['deleted_at'] !== null || $row['invoice_id'] !== null || $row['version'] !== $source['version']) {
            throw ValidationException::withMessages(['held_cart' => 'Keranjang tertahan berubah, dibatalkan atau sudah dibayar. Muat daftar dan periksa kembali.']);
        }

        return $row;
    }

    public function complete(array $source, int $actorId, string $invoiceId): void
    {
        $row = $this->checkoutSource($source, $actorId);
        $this->carts->write($row['id'], ['invoice_id' => $invoiceId, 'version' => $row['version'] + 1]);
        $this->carts->audit($row['id'], $actorId, 'completed');
    }

    private function assertOpen(array $row): void
    {
        if ($row['deleted_at'] !== null || $row['invoice_id'] !== null) {
            $this->stale();
        }
    }

    private function stale(): never
    {
        throw ValidationException::withMessages(['version' => 'Keranjang tertahan sudah berubah. Muat ulang daftar sebelum melanjutkan.']);
    }

    private function reference(array $row): array
    {
        return ['id' => $row['id'], 'version' => $row['version'], 'label' => $row['label']];
    }

    private function normalize(string $id, array $input, int $actorId): array
    {
        if ($actorId < 1 || array_diff(array_keys($input), ['version', 'label', 'warehouse_id', 'customer_id', 'lines', 'pricing'])) {
            throw ValidationException::withMessages(['cart' => 'Input keranjang tertahan tidak diizinkan.']);
        }
        $rules = ['required', 'ulid'];
        $data = Validator::make(['id' => $id, ...$input], ['id' => $rules,
            'version' => ['required', 'integer', 'min:0', 'max:2147483646'], 'label' => ['sometimes', 'nullable', 'string', 'max:80'],
            'warehouse_id' => $rules, 'customer_id' => $rules, 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:item_id,unit_id,quantity'], 'lines.*.item_id' => $rules, 'lines.*.unit_id' => $rules,
            'lines.*.quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'pricing' => ['sometimes', 'array:discount_type,discount_value,tax_rate']])->validate();
        $lines = [];
        $seen = [];
        foreach ($data['lines'] as $index => $line) {
            $key = strtolower($line['item_id'].':'.$line['unit_id']);
            $quantity = BigDecimal::of($line['quantity'])->toScale(6);
            if (isset($seen[$key]) || $quantity->isLessThanOrEqualTo(0)) {
                throw ValidationException::withMessages(["lines.{$index}.quantity" => 'Kuantitas harus positif dan barang/satuan tidak boleh berulang.']);
            }
            $seen[$key] = true;
            $lines[] = ['item_id' => strtolower($line['item_id']), 'unit_id' => strtolower($line['unit_id']), 'quantity' => (string) $quantity];
        }
        try {
            $pricing = InvoiceAmounts::normalize($data['pricing'] ?? []);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['pricing' => $e->getMessage()]);
        }

        return ['version' => (int) $data['version'], 'label' => trim($data['label'] ?? ''), 'warehouse_id' => strtolower($data['warehouse_id']),
            'customer_id' => strtolower($data['customer_id']), 'lines' => $lines, 'pricing' => $pricing];
    }
}
