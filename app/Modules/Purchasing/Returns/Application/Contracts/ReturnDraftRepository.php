<?php

namespace App\Modules\Purchasing\Returns\Application\Contracts;

interface ReturnDraftRepository
{
    public function lock(string $id, bool $trashed = false): array;

    public function save(array $header, array $lines, ?string $id = null, ?string $demoKey = null): string;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function post(string $id, string $issueId, string $postedAt, int $version, ?int $actorId): void;

    public function demoExists(string $key): bool;

    public function paginate(int $page, array $filters): array;
}
