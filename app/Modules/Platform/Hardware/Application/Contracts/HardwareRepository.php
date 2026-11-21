<?php

namespace App\Modules\Platform\Hardware\Application\Contracts;

use App\Modules\Platform\Hardware\Application\DTOs\LabelJobData;
use App\Modules\Platform\Hardware\Application\DTOs\PrinterProfileData;

interface HardwareRepository
{
    public function profiles(bool $deleted = false): array;

    public function profile(string $id, bool $lock = false): ?PrinterProfileData;

    public function saveProfile(array $data, ?string $id = null): PrinterProfileData;

    public function changeProfile(string $id, array $changes): PrinterProfileData;

    public function hasOpenJobs(string $printerId): bool;

    public function findJobByKey(int $actorId, string $key): ?LabelJobData;

    public function job(string $id, bool $lock = false): ?LabelJobData;

    public function createJob(array $data): LabelJobData;

    public function claim(string $jobId, string $hash, string $expiresAt): string;

    public function attempt(string $jobId): ?array;

    public function complete(string $jobId, string $status, ?string $errorCode): LabelJobData;

    public function history(int $actorId, bool $all, string $search, string $status, int $page): array;

    public function audit(string $event, int $actorId, array $metadata): void;
}
