<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Modules\Platform\Identity\Application\Contracts\InitialOwnerRepository;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateInitialOwner
{
    public function __construct(private InitialOwnerRepository $owners) {}

    public function handle(#[\SensitiveParameter] array $input): int
    {
        if (! app()->runningInConsole() || ! app('pos.standalone.enabled') || config('standalone.enabled') !== true) {
            throw new DomainException('Setup pemilik hanya tersedia pada console instalasi standalone.');
        }
        if (array_diff(array_keys($input), ['name', 'email', 'password', 'password_confirmation']) !== []) {
            throw ValidationException::withMessages(['profile' => 'Field setup tidak dikenal.']);
        }
        $input['name'] = is_string($input['name'] ?? null) ? trim($input['name']) : null;
        $input['email'] = is_string($input['email'] ?? null) ? strtolower(trim($input['email'])) : null;
        $profile = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('Password maksimal 72 byte.');
                    }
                }],
        ])->validate();

        return DB::transaction(function () use ($profile): int {
            if (! $this->owners->lockSystemRole()) {
                throw new DomainException('Role produksi super-system belum tersedia.');
            }
            if (! $this->owners->isPristine()) {
                throw new DomainException('Setup ditolak: database sudah berisi akun, data atau pengaturan operasional.');
            }
            $id = $this->owners->create($profile);
            $this->owners->audit($id);

            return $id;
        }, 3);
    }
}
