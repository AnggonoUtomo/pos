<?php

namespace App\Modules\Sales\Returns\Application\Contracts;

interface ReturnDraftRepository
{
    public function lock(string $id, bool $trashed = false): array;

    public function save(array $header, array $lines, ?string $id = null): string;

    public function lifecycle(string $id, bool $restore): void;

    public function post(string $id, string $receiptId, string $postedAt, int $version, ?int $actorId): void;

    public function paginate(int $page, string $search, bool $trashed): array;

    public function demoExists(string $key): bool;
}
