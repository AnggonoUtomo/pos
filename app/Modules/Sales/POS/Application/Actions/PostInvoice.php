<?php

namespace App\Modules\Sales\POS\Application\Actions;

use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\SalePaymentRequest;
use App\Modules\Inventory\Stock\Application\Contracts\SaleStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\SaleStockRequest;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\DTOs\InvoiceReceipt;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PostInvoice
{
    public function __construct(private InvoiceRepository $invoices, private CheckCart $cart, private WarehouseLookup $warehouses,
        private SaleStockWriter $stock, private SalePaymentWriter $payments) {}

    public function handle(array $input, int $actorId): array
    {
        $input = $this->normalize($input);
        if ($actorId < 1) {
            throw ValidationException::withMessages(['cart' => 'Actor transaksi tidak valid.']);
        }
        $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($input, $actorId, $hash): array {
                if ($this->warehouses->findActive($input['warehouse_id'], lock: true) === null) {
                    throw ValidationException::withMessages(['warehouse_id' => 'Pilih gudang aktif yang tersedia.']);
                }
                $existing = $this->invoices->lockByKey($actorId, $input['idempotency_key']);
                if ($existing !== null) {
                    return $this->replay($existing, $hash);
                }
                $lines = array_map(fn ($line) => new CartLineData($line['item_id'], $line['unit_id'], $line['quantity']), $input['lines']);
                $quote = $this->cart->handle($input['warehouse_id'], $input['customer_id'], $lines, lock: true);
                if (! hash_equals($quote['fingerprint'], $input['quote_fingerprint'])) {
                    throw ValidationException::withMessages(['quote_fingerprint' => 'Harga atau konfigurasi berubah. Periksa ulang keranjang sebelum membayar.']);
                }
                $id = strtolower((string) Str::ulid());
                $number = 'POS-'.strtoupper($id);
                $rows = $stockLines = [];
                foreach ($quote['lines'] as $index => $line) {
                    $lineId = strtolower((string) Str::ulid());
                    $rows[] = $line + ['id' => $lineId, 'line_number' => $index + 1, 'discount_amount' => '0.00', 'tax_amount' => '0.00'];
                    $stockLines[] = ['source_line_id' => $lineId, 'item_id' => $line['item_id'], 'base_quantity' => $line['base_quantity']];
                }
                $stock = $this->stock->issue(new SaleStockRequest($input['warehouse_id'], $id, $number, $stockLines, $actorId));
                $cost = BigDecimal::zero()->toScale(6);
                foreach ($rows as &$row) {
                    $row['total_cost'] = $stock->lineCosts[$row['id']] ?? throw new \RuntimeException('HPP baris invoice tidak tersedia.');
                    $cost = $cost->plus($row['total_cost']);
                }
                unset($row);
                if ($cost->isGreaterThan('999999999999999999999999.999999')) {
                    throw ValidationException::withMessages(['cart' => 'Total HPP melebihi batas.']);
                }
                $payment = $this->payments->receive(new SalePaymentRequest($id, $number, $quote['subtotal'], $input['payments'], $actorId));
                $warehouse = $quote['warehouse'];
                $customer = $quote['customer'];
                $this->invoices->createPosted(['id' => $id, 'number' => $number, 'actor_id' => $actorId, 'idempotency_key' => $input['idempotency_key'],
                    'request_hash' => $hash, 'warehouse_id' => $warehouse->id, 'warehouse_code' => $warehouse->code, 'warehouse_name' => $warehouse->name,
                    'customer_id' => $customer->id, 'customer_code' => $customer->code, 'customer_name' => $customer->name,
                    'customer_level_id' => $customer->levelId, 'customer_level_name' => $customer->levelName,
                    'subtotal' => $quote['subtotal'], 'discount_amount' => '0.00', 'tax_amount' => '0.00', 'total' => $quote['subtotal'],
                    'total_cost' => (string) $cost, 'stock_issue_id' => $stock->id, 'payment_receipt_id' => $payment->id,
                    'payments' => $payment->snapshot, 'posted_at' => now()], $rows);
                $this->invoices->audit($id, $actorId);

                return InvoiceReceipt::fromRecord($this->invoices->find($id));
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            // A competing transaction on another warehouse can win the actor/key unique index.
            return DB::transaction(function () use ($actorId, $input, $hash): array {
                $existing = $this->invoices->lockByKey($actorId, $input['idempotency_key']);
                if ($existing === null) {
                    throw ValidationException::withMessages(['cart' => 'Konflik transaksi. Muat ulang dan periksa ulang keranjang.']);
                }

                return $this->replay($existing, $hash);
            }, attempts: 3);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }
    }

    private function replay(array $existing, string $hash): array
    {
        if (! hash_equals($existing['request_hash'], $hash)) {
            throw ValidationException::withMessages(['idempotency_key' => 'Kunci transaksi sudah dipakai dengan isi berbeda.']);
        }

        return InvoiceReceipt::fromRecord($existing);
    }

    private function normalize(array $input): array
    {
        $allowed = ['idempotency_key', 'warehouse_id', 'customer_id', 'quote_fingerprint', 'lines', 'payments'];
        if (array_diff(array_keys($input), $allowed) !== []) {
            throw ValidationException::withMessages(['cart' => 'Payload checkout mengandung field yang tidak diizinkan.']);
        }
        $rules = ['required', 'ulid'];
        $data = Validator::make($input, ['idempotency_key' => $rules, 'warehouse_id' => $rules, 'customer_id' => $rules,
            'quote_fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:item_id,unit_id,quantity'], 'lines.*.item_id' => $rules, 'lines.*.unit_id' => $rules,
            'lines.*.quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'payments' => ['required', 'array', 'list', 'min:1', 'max:10'], 'payments.*' => ['required', 'array:method_id,amount'],
            'payments.*.method_id' => $rules, 'payments.*.amount' => ['required', 'string', 'regex:/^\d{1,28}(?:\.\d{1,2})?$/D']])->validate();
        foreach (['idempotency_key', 'warehouse_id', 'customer_id'] as $key) {
            $data[$key] = strtolower($data[$key]);
        }
        foreach ($data['lines'] as &$line) {
            $line = ['item_id' => strtolower($line['item_id']), 'unit_id' => strtolower($line['unit_id']),
                'quantity' => (string) BigDecimal::of($line['quantity'])->toScale(6)];
        }
        unset($line);
        usort($data['lines'], fn ($a, $b) => [$a['item_id'], $a['unit_id']] <=> [$b['item_id'], $b['unit_id']]);
        foreach ($data['payments'] as &$row) {
            $row = ['method_id' => strtolower($row['method_id']), 'amount' => (string) BigDecimal::of($row['amount'])->toScale(2)];
        }
        unset($row);
        usort($data['payments'], fn ($a, $b) => $a['method_id'] <=> $b['method_id']);

        return $data;
    }
}
