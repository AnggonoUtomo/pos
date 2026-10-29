<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Actions\PostReturnDraft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReturnRefundFixture
{
    public static function invoice(string $price = '100.00', bool $withBox = false): array
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Fixture refund hanya pada testing disposable.');
        }
        $source = SalesReturnFixture::seed();
        $line = $source['lines'][0];
        DB::table('item_prices')->where('item_id', $line['item_id'])->update(['amount' => $price]);
        $lines = [['item_id' => $line['item_id'], 'unit_id' => $line['unit_id'], 'quantity' => '1']];
        if ($withBox) {
            $lines[] = ['item_id' => $line['item_id'], 'unit_id' => DB::table('units')->where('code', 'BOX')->value('id'), 'quantity' => '0.5'];
        }
        $quote = app(CheckCart::class)->handle($source['warehouse_id'], $source['customer_id'],
            array_map(fn ($row) => new CartLineData($row['item_id'], $row['unit_id'], $row['quantity']), $lines));

        $actor = User::factory()->create();
        CashierShiftFixture::open($actor->id);

        return app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()),
            'warehouse_id' => $source['warehouse_id'], 'customer_id' => $source['customer_id'], 'quote_fingerprint' => $quote['fingerprint'],
            'lines' => $lines, 'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => $quote['subtotal']]]], $actor->id);
    }

    public static function posted(array $invoice, string $quantity = '1', int $line = 0): string
    {
        $id = app(ManageReturnDraft::class)->save(['invoice_id' => $invoice['id'],
            'lines' => [['invoice_line_id' => $invoice['lines'][$line]['id'], 'quantity' => $quantity]]]);
        app(PostReturnDraft::class)->handle($id, 1);

        return $id;
    }
}
