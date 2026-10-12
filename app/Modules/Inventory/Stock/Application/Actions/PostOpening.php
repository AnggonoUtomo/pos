<?php

namespace App\Modules\Inventory\Stock\Application\Actions;

use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostOpening
{
    public function __construct(private StockRepository $stock, private PrepareOpening $prepare) {}

    public function handle(string $id, ?int $actorId = null): void
    {
        DB::transaction(function () use ($id, $actorId): void {
            $record = $this->stock->lockDraft($id);
            if ($record['status'] === 'posted') {
                return;
            }
            if ($record['status'] !== 'draft') {
                throw new InvalidArgumentException('Hanya draft yang dapat diposting.');
            }
            [$header, $lines] = $this->prepare->handle($record);
            $old = array_column($record['lines'], null, 'item_id');
            foreach ($lines as &$line) {
                $previous = $old[$line['item_id']];
                if ($previous['conversion_ratio'] !== $line['conversion_ratio'] || $previous['base_unit_id'] !== $line['base_unit_id']) {
                    throw new InvalidArgumentException('Konversi draft berubah. Edit dan simpan ulang sebelum posting.');
                }
                if ($this->stock->hasBalance($header['warehouse_id'], $line['item_id'])) {
                    throw new InvalidArgumentException('Barang sudah memiliki histori stok di gudang ini. Stok awal hanya boleh sekali.');
                }
                $line['id'] = $previous['id'];
            }
            unset($line);
            $this->stock->postOpening($id, $header, $lines, $actorId);
        }, attempts: 3);
    }
}
