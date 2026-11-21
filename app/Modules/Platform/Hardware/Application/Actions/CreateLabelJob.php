<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemLabelCatalog;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\LabelJobData;
use App\Modules\Platform\Hardware\Domain\Services\LabelSpecification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateLabelJob
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability, private ItemLabelCatalog $catalog) {}

    public function handle(array $input, int $actorId): LabelJobData
    {
        abort_unless($this->availability->printingEnabled(), 409, 'Pengiriman cetak belum diaktifkan. Pratinjau tetap tersedia.');
        if (array_diff(array_keys($input), ['printer_id', 'item_id', 'unit_id', 'kind', 'copies', 'idempotency_key'])) {
            throw ValidationException::withMessages(['label' => 'Field label tidak diizinkan.']);
        }
        $data = Validator::make($input, ['printer_id' => ['required', 'ulid'], 'kind' => ['required', 'in:product_label,test_label'],
            'item_id' => ['required_if:kind,product_label', 'nullable', 'ulid'], 'unit_id' => ['required_if:kind,product_label', 'nullable', 'ulid'],
            'copies' => ['required', 'integer', 'between:1,100'], 'idempotency_key' => ['required', 'uuid']])->validate();
        $data['copies'] = LabelSpecification::copies((int) $data['copies']);
        $hash = hash('sha256', json_encode([$data['printer_id'], $data['item_id'] ?? null, $data['unit_id'] ?? null, $data['kind'], $data['copies']], JSON_THROW_ON_ERROR));
        $existing = $this->repository->findJobByKey($actorId, $data['idempotency_key']);
        if ($existing !== null) {
            abort_unless(hash_equals($existing->requestHash, $hash), 409, 'Kunci permintaan sudah digunakan untuk label berbeda.');

            return $existing;
        }
        try {
            return DB::transaction(function () use ($data, $actorId, $hash): LabelJobData {
                $profile = $this->repository->profile($data['printer_id'], true);
                abort_if($profile === null || ! $profile->isActive || $profile->deletedAt !== null, 422, 'Profil printer tidak aktif.');
                abort_unless(in_array($profile->gatewayHost, $this->availability->allowedHosts(), true), 422, 'Host gateway tidak diizinkan.');
                if ($data['kind'] === 'test_label') {
                    $snapshot = ['name' => 'DEMO UJI LABEL', 'sku' => 'DEMO-QZ-LABEL', 'unit_name' => 'Unit', 'barcode' => '001234567890'];
                } else {
                    $label = $this->catalog->find($data['item_id'], $data['unit_id']);
                    abort_if($label === null, 422, 'Barang/satuan barcode sudah tidak tersedia.');
                    $snapshot = $label->toArray();
                }
                abort_unless(preg_match('/^[\x20-\x7e]{1,64}$/D', $snapshot['barcode']), 422, 'Barcode tidak dapat dicetak sebagai Code128.');
                $job = $this->repository->createJob(['printer_id' => $profile->id, 'created_by' => $actorId,
                    'idempotency_key' => $data['idempotency_key'], 'request_hash' => $hash, 'kind' => $data['kind'],
                    'snapshot' => $snapshot, 'printer_snapshot' => $profile->toArray(), 'copies' => $data['copies'], 'original_job_id' => null, 'reason' => null]);
                $this->repository->audit('label.created', $actorId, ['job_id' => $job->id, 'printer_id' => $profile->id, 'copies' => $job->copies]);

                return $job;
            }, 3);
        } catch (QueryException $exception) {
            $existing = $this->repository->findJobByKey($actorId, $data['idempotency_key']);
            if ($existing === null) {
                throw $exception;
            }
            abort_unless(hash_equals($existing->requestHash, $hash), 409, 'Kunci permintaan sudah digunakan untuk label berbeda.');

            return $existing;
        }
    }
}
