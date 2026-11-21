<?php

namespace App\Modules\Platform\Hardware\Application\Actions;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\PrinterProfileData;
use App\Modules\Platform\Hardware\Domain\Services\LabelSpecification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SavePrinterProfile
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability) {}

    public function handle(array $input, int $actorId, ?string $id = null): PrinterProfileData
    {
        abort_unless($this->availability->enabled(), 404);
        if (array_diff(array_keys($input), ['name', 'printer_name', 'gateway_host', 'width_mm', 'height_mm', 'dpi', 'orientation', 'is_active', 'version'])) {
            throw ValidationException::withMessages(['profile' => 'Field profil tidak diizinkan.']);
        }
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:150', 'not_regex:/[\x00-\x1f\x7f]/'],
            'printer_name' => ['required', 'string', 'max:150', 'not_regex:/[\x00-\x1f\x7f]/'],
            'gateway_host' => ['required', 'string', Rule::in($this->availability->allowedHosts())],
            'width_mm' => ['required', 'integer', 'between:20,100'], 'height_mm' => ['required', 'integer', 'between:20,150'],
            'dpi' => ['required', 'integer', 'in:203'], 'orientation' => ['required', 'in:portrait,landscape'],
            'is_active' => ['required', 'boolean'], 'version' => ['required', 'integer', 'min:0', 'max:2147483646'],
        ])->validate();
        foreach (['width_mm', 'height_mm', 'dpi', 'version'] as $field) {
            $data[$field] = (int) $data[$field];
        }
        $data['is_active'] = (bool) $data['is_active'];
        LabelSpecification::validate($data);

        return DB::transaction(function () use ($data, $id, $actorId): PrinterProfileData {
            if ($id !== null) {
                $profile = $this->repository->profile($id, true);
                abort_if($profile === null || $profile->deletedAt !== null, 404);
                abort_unless($profile->version === $data['version'], 409, 'Profil berubah. Muat ulang sebelum menyimpan.');
                abort_if($this->repository->hasOpenJobs($id), 409, 'Selesaikan atau batalkan job profil ini lebih dahulu.');
            }
            $saved = $this->repository->saveProfile($data, $id);
            $this->repository->audit($id ? 'printer.updated' : 'printer.created', $actorId,
                ['printer_id' => $saved->id, 'version' => $saved->version, 'changes' => $saved->toArray()]);

            return $saved;
        }, 3);
    }
}
