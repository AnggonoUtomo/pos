<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemClassificationRepository;
use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ManageItemClassification
{
    public function __construct(private ItemClassificationRepository $repository) {}

    public function save(ClassificationKind $kind, array $input, ?string $id = null): string
    {
        $data = Validator::make([
            'code' => is_string($input['code'] ?? null) ? strtoupper(trim($input['code'])) : ($input['code'] ?? null),
            'name' => is_string($input['name'] ?? null) ? trim($input['name']) : ($input['name'] ?? null),
            'is_active' => array_key_exists('is_active', $input) ? $input['is_active'] : true,
        ], ['code' => ['required', 'string', 'max:32'], 'name' => ['required', 'string', 'max:150'],
            'is_active' => ['required', 'boolean']])->validate();

        return DB::transaction(function () use ($kind, $data, $id): string {
            if ($id !== null) {
                $this->repository->lock($kind, $id);
                if (! $data['is_active']) {
                    $this->assertUnused($kind, $id);
                }
            }

            return $this->repository->save($kind, $data, $id);
        }, 3);
    }

    public function delete(ClassificationKind $kind, string $id): void
    {
        DB::transaction(function () use ($kind, $id): void {
            $this->repository->lock($kind, $id);
            $this->assertUnused($kind, $id);
            $this->repository->delete($kind, $id);
        }, 3);
    }

    public function restore(ClassificationKind $kind, string $id): void
    {
        DB::transaction(function () use ($kind, $id): void {
            $this->repository->lock($kind, $id, deleted: true);
            $this->repository->restore($kind, $id);
        }, 3);
    }

    private function assertUnused(ClassificationKind $kind, string $id): void
    {
        if ($this->repository->referenced($kind, $id)) {
            throw ValidationException::withMessages(['inventory' => 'Master masih digunakan barang, termasuk barang terhapus, dan tidak boleh dihapus atau dinonaktifkan.']);
        }
    }
}
