<?php

namespace App\Modules\Purchasing\Returns\Application\Contracts;

interface ReturnDraftRepository
{
    public function lock(string $id): array;

    public function save(array $header, array $lines, ?string $id = null): string;

    public function paginate(int $page, array $filters): array;
}
