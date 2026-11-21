<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\PrinterProfileData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangePrinterProfileStatus
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability) {}

    public function handle(string $id, string $operation, array $input, int $actorId): PrinterProfileData
    {
        abort_unless($this->availability->enabled(), 404);
        if (array_diff(array_keys($input), ['version', 'is_active'])) {
            throw ValidationException::withMessages(['profile' => 'Field status tidak diizinkan.']);
        }
        $data = Validator::make($input, ['version' => ['required', 'integer', 'min:1', 'max:2147483646'],
            'is_active' => [$operation === 'status' ? 'required' : 'prohibited', 'boolean']])->validate();

        return DB::transaction(function () use ($id, $operation, $data, $actorId): PrinterProfileData {
            $profile = $this->repository->profile($id, true);
            abort_if($profile === null, 404);
            abort_unless($profile->version === (int) $data['version'], 409, 'Profil berubah. Muat ulang halaman.');
            abort_if($this->repository->hasOpenJobs($id), 409, 'Selesaikan atau batalkan job sebelum mengubah status profil.');
            abort_unless(in_array($operation, ['delete', 'restore', 'status'], true), 422);
            abort_if($operation !== 'restore' && $profile->deletedAt !== null, 404);
            $changes = match ($operation) {
                'delete' => ['deleted_at' => now()->utc()],
                'restore' => ['deleted_at' => null],
                default => ['is_active' => (bool) $data['is_active']],
            };
            $saved = $this->repository->changeProfile($id, $changes);
            $this->repository->audit('printer.'.$operation, $actorId, ['printer_id' => $id, 'version' => $saved->version]);

            return $saved;
        }, 3);
    }
}
