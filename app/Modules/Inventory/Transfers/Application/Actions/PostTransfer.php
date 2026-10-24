<?php

namespace App\Modules\Inventory\Transfers\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\TransferStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\TransferStockRequest;
use App\Modules\Inventory\Transfers\Application\Contracts\TransferDraftRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PostTransfer
{
    public function __construct(private TransferDraftRepository $drafts, private TransferStockWriter $stock) {}

    public function handle(string $id, array $input, int $actorId): void
    {
        if (array_diff(array_keys($input), ['version'])) {
            throw ValidationException::withMessages(['transfer' => 'Posting hanya menerima version.']);
        }
        Validator::make($input, ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        try {
            DB::transaction(function () use ($id, $input, $actorId): void {
                $record = $this->drafts->lock($id);
                if ($record['status'] === 'posted') {
                    return;
                }
                if ($record['status'] !== 'draft' || $record['version'] !== (int) $input['version']) {
                    throw ValidationException::withMessages(['version' => 'Draft sudah berubah. Muat ulang daftar.']);
                }
                $this->stock->transfer(new TransferStockRequest($id, $record['number'], $record['source_warehouse_id'],
                    $record['destination_warehouse_id'], $record['lines'], $actorId));
                $this->drafts->markPosted($id, $actorId);
            }, 3);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['transfer' => $e->getMessage()]);
        }
    }
}
