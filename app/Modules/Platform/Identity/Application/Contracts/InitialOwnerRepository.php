<?php

namespace App\Modules\Platform\Identity\Application\Contracts;

interface InitialOwnerRepository
{
    public function lockSystemRole(): bool;

    public function isPristine(): bool;

    public function create(#[\SensitiveParameter] array $profile): int;

    public function audit(int $id): void;
}
