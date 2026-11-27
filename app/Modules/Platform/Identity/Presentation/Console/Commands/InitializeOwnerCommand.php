<?php

namespace App\Modules\Platform\Identity\Presentation\Console\Commands;

use App\Modules\Platform\Identity\Application\Actions\CreateInitialOwner;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use JsonException;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Throwable;

class InitializeOwnerCommand extends Command
{
    protected $signature = 'identity:initialize-owner {--stdin : Baca profil JSON dari stdin privat, maksimal 8 KiB}';

    protected $description = 'Buat satu pemilik pertama hanya pada instalasi standalone baru tanpa akun/data toko';

    public function handle(CreateInitialOwner $owners): int
    {
        if (! app('pos.standalone.enabled') || config('standalone.enabled') !== true) {
            $this->error('Setup pemilik hanya tersedia pada instalasi standalone.');

            return self::FAILURE;
        }
        try {
            if ($this->option('stdin')) {
                $stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;
                $payload = stream_get_contents($stream ?? STDIN, 8193);
                if ($payload === false || strlen($payload) > 8192) {
                    throw new DomainException('Input setup tidak valid.');
                }
                $profile = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
                if (! is_array($profile) || array_is_list($profile)) {
                    throw new DomainException('Input setup harus berupa objek profil.');
                }
            } else {
                if (! $this->input->isInteractive()) {
                    throw new DomainException('Gunakan input stdin privat untuk setup noninteraktif.');
                }
                $profile = ['name' => $this->ask('Nama pemilik'), 'email' => $this->ask('Email pemilik'),
                    'password' => $this->secret('Password awal, 12-72 byte', false),
                    'password_confirmation' => $this->secret('Konfirmasi password', false)];
            }
            $owners->handle($profile);
            $this->info('Pemilik pertama berhasil dibuat. Login menggunakan identitas yang baru diisi.');

            return self::SUCCESS;
        } catch (ValidationException) {
            $this->error('Profil/password tidak valid. Periksa nama, email, panjang password dan konfirmasinya.');
        } catch (JsonException) {
            $this->error('Input JSON setup tidak valid.');
        } catch (DomainException) {
            $this->error('Setup ditolak. Periksa input serta prasyarat role dan database baru tanpa akun/data operasional.');
        } catch (Throwable) {
            $this->error('Setup gagal; tidak ada perubahan akun yang disimpan. Periksa prasyarat database dan audit.');
        }

        return self::FAILURE;
    }
}
