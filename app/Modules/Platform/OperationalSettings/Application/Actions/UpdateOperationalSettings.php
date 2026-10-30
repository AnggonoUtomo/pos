<?php

namespace App\Modules\Platform\OperationalSettings\Application\Actions;

use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePreferences;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateOperationalSettings
{
    public function __construct(private OperationalSettingsRepository $settings) {}

    public function handle(array $input, ?int $actorId): void
    {
        if (array_diff(array_keys($input), ['receipt_paper', 'backup_enabled', 'backup_time', 'version', 'table_per_page'])) {
            throw ValidationException::withMessages(['settings' => 'Field pengaturan tidak diizinkan.']);
        }
        $data = Validator::make($input, [
            'receipt_paper' => ['required', 'in:a4,58mm,80mm'], 'backup_enabled' => ['required', 'boolean'],
            'backup_time' => ['required', 'date_format:H:i'], 'version' => ['required', 'integer', 'min:0', 'max:2147483646'],
            'table_per_page' => ['sometimes', 'required', 'integer', Rule::in(TablePreferences::SIZES)],
        ])->validate();
        $version = (int) $data['version'];
        unset($data['version']);
        DB::transaction(fn () => $this->settings->update($data, $version, $actorId), 3);
    }
}
