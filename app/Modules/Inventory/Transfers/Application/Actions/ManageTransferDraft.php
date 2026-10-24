<?php

namespace App\Modules\Inventory\Transfers\Application\Actions;

use App\Modules\Inventory\Transfers\Application\Contracts\TransferDraftRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ManageTransferDraft
{
    public function __construct(private TransferDraftRepository $drafts, private PrepareTransferDraft $prepare) {}

    public function save(array $input, ?string $id = null, ?string $demoKey = null): string
    {
        if (array_diff(array_keys($input), ['source_warehouse_id', 'destination_warehouse_id', 'notes', 'lines', 'version']) !== []) {
            throw ValidationException::withMessages(['transfer' => 'Payload draft mengandung field yang tidak diizinkan.']);
        }
        Validator::make($input, ['version' => [$id === null ? 'sometimes' : 'required', 'integer', 'min:1', 'max:2147483646']])->validate();

        return DB::transaction(function () use ($input, $id, $demoKey): string {
            if ($id !== null) {
                $this->assertEditable($this->drafts->lock($id), $input['version']);
            }
            [$header, $lines] = $this->prepare->handle($input);
            if ($id === null && $demoKey !== null) {
                Validator::make(['demo_key' => $demoKey], ['demo_key' => ['required', 'string', 'max:64']])->validate();
                $header['demo_key'] = $demoKey;
            }

            return $this->drafts->save($header, $lines, $id);
        }, 3);
    }

    public function delete(string $id, array $input): void
    {
        $this->lifecycle($id, $input, false);
    }

    public function restore(string $id, array $input): void
    {
        $this->lifecycle($id, $input, true);
    }

    private function lifecycle(string $id, array $input, bool $restore): void
    {
        if (array_diff(array_keys($input), ['version']) !== []) {
            throw ValidationException::withMessages(['transfer' => 'Operasi lifecycle hanya menerima version.']);
        }
        Validator::make($input, ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        DB::transaction(function () use ($id, $input, $restore): void {
            $this->assertEditable($this->drafts->lock($id, deleted: $restore), $input['version']);
            $restore ? $this->drafts->restore($id) : $this->drafts->delete($id);
        }, 3);
    }

    private function assertEditable(array $record, mixed $version): void
    {
        if ($record['status'] !== 'draft' || $record['version'] !== (int) $version) {
            throw ValidationException::withMessages(['version' => 'Draft sudah berubah atau tidak dapat diedit. Muat ulang daftar.']);
        }
    }
}
