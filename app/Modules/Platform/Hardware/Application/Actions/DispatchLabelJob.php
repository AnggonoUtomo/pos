<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Domain\Services\PrintJobLifecycle;
use Illuminate\Support\Facades\DB;
use LogicException;

class DispatchLabelJob
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability) {}

    public function handle(string $id, int $actorId): array
    {
        abort_unless($this->availability->printingEnabled(), 409, 'Pengiriman cetak dinonaktifkan.');

        return DB::transaction(function () use ($id, $actorId): array {
            $job = $this->repository->job($id, true);
            abort_if($job === null, 404);
            abort_unless($job->createdBy === $actorId, 403);
            try {
                PrintJobLifecycle::assertClaimable($job->status);
            } catch (LogicException $exception) {
                abort(409, $exception->getMessage());
            }
            $profile = $this->repository->profile($job->printerId, true);
            abort_if($profile === null || ! $profile->isActive || $profile->deletedAt !== null, 409, 'Profil printer tidak aktif.');
            abort_unless($profile->version === $job->printer['version'] && in_array($profile->gatewayHost, $this->availability->allowedHosts(), true), 409, 'Profil/host berubah; buat job baru.');
            $token = bin2hex(random_bytes(32));
            $expires = now()->utc()->addMinutes(2);
            $attemptId = $this->repository->claim($id, hash('sha256', $token), $expires->format('Y-m-d H:i:s'));
            $this->repository->audit('label.claimed', $actorId, ['job_id' => $id, 'status' => 'sending']);

            return ['job' => $this->repository->job($id)->toArray(), 'attempt_id' => $attemptId, 'token' => $token, 'expires_at' => $expires->toIso8601String()];
        }, 3);
    }
}
