<?php

namespace App\Modules\Platform\CompanySettings\Application\Actions;

use App\Modules\Platform\CompanySettings\Application\Contracts\CompanySettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateCompanySettings
{
    public function __construct(private CompanySettingsRepository $settings) {}

    public function handle(array $input, ?int $actorId): void
    {
        if (array_diff(array_keys($input), ['name', 'address', 'phone', 'receipt_footer', 'version'])) {
            throw ValidationException::withMessages(['settings' => 'Field pengaturan tidak diizinkan.']);
        }
        foreach (['name', 'address', 'phone', 'receipt_footer'] as $field) {
            if (is_string($input[$field] ?? null)) {
                $input[$field] = trim($input[$field]);
            }
        }
        $data = Validator::make($input, ['name' => ['required', 'string', 'max:150'], 'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'], 'receipt_footer' => ['nullable', 'string', 'max:300'],
            'version' => ['required', 'integer', 'min:0', 'max:2147483646']])->validate();
        $version = (int) $data['version'];
        unset($data['version']);
        foreach (['address', 'phone', 'receipt_footer'] as $key) {
            $data[$key] = $data[$key] ?? '';
        }
        DB::transaction(fn () => $this->settings->update($data, $version, $actorId), 3);
    }
}
