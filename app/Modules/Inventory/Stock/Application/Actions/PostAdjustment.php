<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\AdjustmentRepository;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PostAdjustment
{
    public function __construct(private AdjustmentRepository $records, private WriteOperationalStock $stock, private WarehouseLookup $warehouses) {}

    public function handle(array $input, int $actorId): string
    {
        if (array_diff(array_keys($input), ['idempotency_key', 'warehouse_id', 'item_id', 'direction', 'quantity', 'unit_cost', 'reason'])) {
            throw ValidationException::withMessages(['adjustment' => 'Field koreksi tidak diizinkan.']);
        }
        $decimal = ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'];
        $data = Validator::make($input, ['idempotency_key' => ['required', 'ulid'], 'warehouse_id' => ['required', 'ulid'],
            'item_id' => ['required', 'ulid'], 'direction' => ['required', 'in:in,out'], 'quantity' => $decimal,
            'unit_cost' => ['nullable', 'required_if:direction,in', 'prohibited_if:direction,out', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'reason' => ['required', 'string', 'max:500']])->validate();
        foreach (['idempotency_key', 'warehouse_id', 'item_id'] as $key) {
            $data[$key] = strtolower($data[$key]);
        }
        $data['reason'] = trim($data['reason']);
        $data['quantity'] = (string) BigDecimal::of($data['quantity'])->toScale(6);
        $data['unit_cost'] = isset($data['unit_cost']) ? (string) BigDecimal::of($data['unit_cost'])->toScale(6) : null;
        if (! BigDecimal::of($data['quantity'])->isPositive() || $data['reason'] === '' || ($data['direction'] === 'in' && ! BigDecimal::of($data['unit_cost'])->isPositive())) {
            throw ValidationException::withMessages(['adjustment' => 'Kuantitas/harga pokok masuk wajib positif dan alasan wajib diisi.']);
        }
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($data, $hash, $actorId): string {
                $this->warehouses->findActive($data['warehouse_id'], lock: true) ?? throw new InvalidArgumentException('Gudang tidak aktif.');
                $existing = $this->records->find($data['idempotency_key']);
                if ($existing !== null) {
                    if (! hash_equals($existing['request_hash'], $hash) || $existing['posted_by'] !== $actorId) {
                        throw new InvalidArgumentException('Kunci koreksi sudah digunakan dengan isi/actor berbeda.');
                    }

                    return $existing['id'];
                }
                $id = $data['idempotency_key'];
                $value = $this->stock->adjustment($id, $data['warehouse_id'], $data['item_id'], $data['direction'], $data['quantity'], $data['unit_cost'], $actorId);
                $this->records->create(['id' => $id, 'number' => 'AD-'.strtoupper($id), 'request_hash' => $hash,
                    'warehouse_id' => $data['warehouse_id'], 'item_id' => $data['item_id'], 'direction' => $data['direction'],
                    'quantity' => $data['quantity'], 'total_value' => $value, 'reason' => $data['reason'], 'posted_at' => now(), 'posted_by' => $actorId]);

                return $id;
            }, 3);
        } catch (InvalidArgumentException|MathException $e) {
            throw ValidationException::withMessages(['adjustment' => $e->getMessage()]);
        }
    }
}
