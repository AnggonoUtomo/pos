<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteOwnAccount
{
    public function __construct(private AccessRepository $access) {}

    public function handle(int $actorId): void
    {
        DB::transaction(function () use ($actorId): void {
            $this->access->lockRoles();
            $user = $this->access->lockUser($actorId);
            if (in_array('super-system', $user['roles'], true) && $this->access->superUserCount() <= 1) {
                throw ValidationException::withMessages([
                    'account' => 'Super-system terakhir tidak dapat dihapus. Tetapkan super-system lain terlebih dahulu.',
                ]);
            }
            $this->access->audit('identity.account_deleted', $actorId, 'user', $actorId,
                ['roles' => $user['roles']], ['deleted' => true]);
            $this->access->deleteUser($actorId);
        });
        $this->access->clearCache();
    }
}
