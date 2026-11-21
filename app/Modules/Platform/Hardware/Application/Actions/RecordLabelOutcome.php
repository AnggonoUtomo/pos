<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\LabelJobData;
use App\Modules\Platform\Hardware\Domain\Services\PrintJobLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordLabelOutcome
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability) {}

    public function handle(string $id, array $input, int $actorId): LabelJobData
    {
        abort_unless($this->availability->enabled(), 404);
        if (array_diff(array_keys($input), ['attempt_id', 'token', 'status', 'error_code'])) {
            throw ValidationException::withMessages(['outcome' => 'Field hasil tidak diizinkan.']);
        }
        $data = Validator::make($input, ['attempt_id' => ['nullable', 'ulid'], 'token' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'status' => ['required', Rule::in(PrintJobLifecycle::OUTCOMES)], 'error_code' => ['nullable', Rule::in(PrintJobLifecycle::ERRORS)]])->validate();

        return DB::transaction(function () use ($id, $data, $actorId): LabelJobData {
            $job = $this->repository->job($id, true);
            abort_if($job === null, 404);
            abort_unless($job->createdBy === $actorId, 403);
            $code = $data['error_code'] ?? null;
            if ($job->status === 'queued') {
                abort_unless($data['status'] === 'failed_before_send' && $code === 'cancelled', 409, 'Job belum dikirim.');
            } else {
                $attempt = $this->repository->attempt($id);
                abort_unless($attempt !== null && ($data['attempt_id'] ?? '') === $attempt['id']
                    && hash_equals($attempt['claim_hash'], hash('sha256', $data['token'] ?? '')), 403);
                if ($job->status !== 'sending') {
                    abort_unless($job->status === $data['status'] && $attempt['error_code'] === $code, 409, 'Hasil job sudah final.');

                    return $job;
                }
                abort_if(CarbonImmutable::parse($attempt['expires_at'], 'UTC')->isPast(), 409, 'Konfirmasi kedaluwarsa; periksa hasil fisik sebelum cetak ulang.');
            }
            $saved = $this->repository->complete($id, $data['status'], $code);
            $this->repository->audit('label.outcome', $actorId, ['job_id' => $id, 'status' => $saved->status]);

            return $saved;
        }, 3);
    }
}
