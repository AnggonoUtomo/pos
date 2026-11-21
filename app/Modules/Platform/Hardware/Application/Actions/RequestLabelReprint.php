<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\LabelJobData;
use App\Modules\Platform\Hardware\Domain\Services\PrintJobLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RequestLabelReprint
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability) {}

    public function handle(string $id, array $input, int $actorId, bool $manage): LabelJobData
    {
        abort_unless($this->availability->printingEnabled(), 409, 'Pengiriman cetak dinonaktifkan.');
        if (array_diff(array_keys($input), ['reason', 'idempotency_key'])) {
            throw ValidationException::withMessages(['reprint' => 'Field cetak ulang tidak diizinkan.']);
        }
        $data = Validator::make($input, ['reason' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[\x00-\x1f\x7f]/'], 'idempotency_key' => ['required', 'uuid']])->validate();
        $hash = hash('sha256', json_encode(['reprint', $id, $data['reason']], JSON_THROW_ON_ERROR));
        $existing = $this->repository->findJobByKey($actorId, $data['idempotency_key']);
        if ($existing !== null) {
            abort_unless(hash_equals($existing->requestHash, $hash), 409, 'Kunci cetak ulang sudah digunakan.');

            return $existing;
        }
        try {
            return DB::transaction(function () use ($id, $data, $actorId, $manage, $hash): LabelJobData {
                $original = $this->repository->job($id, true);
                abort_if($original === null, 404);
                abort_unless($original->createdBy === $actorId || $manage, 403);
                if ($original->status === 'sending' && $original->expiresAt !== null && CarbonImmutable::parse($original->expiresAt)->isPast()) {
                    $original = $this->repository->complete($id, 'uncertain', 'ack_lost');
                    $this->repository->audit('label.uncertain', $actorId, ['job_id' => $id, 'status' => 'uncertain']);
                }
                abort_unless(PrintJobLifecycle::canReprint($original->status), 409, 'Job masih berjalan/belum dikirim.');
                $profile = $this->repository->profile($original->printerId, true);
                abort_if($profile === null || ! $profile->isActive || $profile->deletedAt !== null, 422, 'Profil printer tidak aktif.');
                abort_unless(in_array($profile->gatewayHost, $this->availability->allowedHosts(), true), 422);
                $job = $this->repository->createJob(['printer_id' => $profile->id, 'created_by' => $actorId,
                    'idempotency_key' => $data['idempotency_key'], 'request_hash' => $hash, 'kind' => $original->kind,
                    'snapshot' => $original->snapshot, 'printer_snapshot' => $profile->toArray(), 'copies' => $original->copies,
                    'original_job_id' => $id, 'reason' => $data['reason']]);
                $this->repository->audit('label.reprint', $actorId, ['job_id' => $job->id, 'original_job_id' => $id, 'reason' => $data['reason'], 'copies' => $job->copies]);

                return $job;
            }, 3);
        } catch (QueryException $exception) {
            $existing = $this->repository->findJobByKey($actorId, $data['idempotency_key']);
            if ($existing === null) {
                throw $exception;
            }
            abort_unless(hash_equals($existing->requestHash, $hash), 409, 'Kunci cetak ulang sudah digunakan.');

            return $existing;
        }
    }
}
