<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\HeldCartRepository;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use Illuminate\Validation\ValidationException;

class HeldCarts
{
    public function __construct(private HeldCartRepository $carts, private SaleCatalog $catalog, private CheckCart $check) {}

    public function listing(int $actorId, int $page): array
    {
        return $this->carts->listOwned($actorId, $page);
    }

    public function recall(string $id, int $actorId): array
    {
        $row = $this->carts->findOwned(strtolower($id), $actorId);
        abort_if($row === null || $row['deleted_at'] !== null || $row['invoice_id'] !== null, 404);
        $payload = $row['payload'];
        $errors = [];
        $quote = null;
        $items = [];
        try {
            $items = array_column($this->catalog->findMany($payload['warehouse_id'], $payload['customer_id'],
                array_values(array_unique(array_column($payload['lines'], 'item_id')))), null, 'id');
            $quote = $this->check->handle($payload['warehouse_id'], $payload['customer_id'],
                array_map(fn ($line) => new CartLineData($line['item_id'], $line['unit_id'], $line['quantity']), $payload['lines']), pricing: $payload['pricing']);
        } catch (ValidationException $e) {
            $errors = $e->errors();
        }
        $lines = [];
        foreach ($payload['lines'] as $index => $line) {
            $snapshot = $row['line_snapshots'][$index];
            $item = $items[$line['item_id']] ?? $snapshot;
            if (! in_array($line['unit_id'], array_column($item['units'], 'id'), true)) {
                $item['units'][] = $snapshot['units'][0];
            }
            $lines[] = ['item' => $item, 'unitId' => $line['unit_id'], 'quantity' => $line['quantity']];
        }

        return ['source' => ['id' => $row['id'], 'version' => $row['version'], 'label' => $row['label']],
            'cart' => ['warehouse_id' => $payload['warehouse_id'], 'customer_id' => $payload['customer_id'], 'pricing' => $payload['pricing'], 'lines' => $lines],
            'quote' => $quote, 'errors' => $errors];
    }
}
