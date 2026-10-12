<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManageOpening
{
    public function __construct(private StockRepository $stock, private PrepareOpening $prepare) {}

    public function create(array $input): string
    {
        return DB::transaction(function () use ($input): string {
            [$header, $lines] = $this->prepare->handle($input);

            return $this->stock->createDraft($header, $lines);
        }, attempts: 3);
    }

    public function update(string $id, array $input): void
    {
        DB::transaction(function () use ($id, $input): void {
            $this->assertDraft($this->stock->lockDraft($id));
            [$header, $lines] = $this->prepare->handle($input);
            $this->stock->replaceDraft($id, $header, $lines);
        }, attempts: 3);
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->assertDraft($this->stock->lockDraft($id));
            $this->stock->deleteDraft($id);
        }, attempts: 3);
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->assertDraft($this->stock->lockDraft($id, deleted: true));
            $this->stock->restoreDraft($id);
        }, attempts: 3);
    }

    private function assertDraft(array $record): void
    {
        if ($record['status'] !== 'draft') {
            throw new InvalidArgumentException('Dokumen posted tidak boleh diubah atau dihapus.');
        }
    }
}
