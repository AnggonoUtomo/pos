<?php

namespace App\Modules\Platform\Identity\Application\Contracts;

interface AccessRepository
{
    public function page(string $tab, string $search, int $page): array;

    public function options(): array;

    public function lockRoles(): array;

    public function actor(int $id): array;

    public function lockUser(int $id): array;

    public function superUserCount(): int;

    public function roleReferences(int $id): int;

    public function createRole(string $name): int;

    public function renameRole(int $id, string $name): void;

    public function deleteRole(int $id): void;

    public function syncPermissions(int $id, array $names): void;

    public function syncRoles(int $id, array $names): void;

    public function deleteUser(int $id): void;

    public function userProfile(int $id): array;

    public function emailExists(string $email, ?int $except = null): bool;

    public function createUser(string $name, string $email, string $password): int;

    public function updateUser(int $id, string $name, string $email): void;

    public function setUserStatus(int $id, bool $active): void;

    public function sessionState(int $id): ?array;

    public function audit(string $event, int $actorId, string $subject, int $id, array $before, array $after): void;

    public function clearCache(): void;
}
