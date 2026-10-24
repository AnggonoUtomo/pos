<?php

namespace App\Modules\Platform\OperationalSettings\Application\Contracts;

interface OperationalSettingsRepository
{
    public function read(): array;

    public function update(array $fields, int $version, ?int $actorId): void;

    public function status(): array;

    public function claimDue(): ?array;

    public function finish(array $claim, string $status, ?string $file): void;
}
