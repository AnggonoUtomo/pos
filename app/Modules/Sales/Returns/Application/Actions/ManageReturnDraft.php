<?php

namespace App\Modules\Sales\Returns\Application\Actions;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Sales\Returns\Domain\ValueObjects\ReturnQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class ManageReturnDraft
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnInvoiceLookup $invoices) {}

    public function save(array $input, ?string $id = null, ?string $demoKey = null): string
    {
        $data = Validator::make($input, ['invoice_id' => ['required', 'ulid'], 'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1', 'max:100'], 'lines.*' => ['array:invoice_line_id,quantity'],
            'lines.*.invoice_line_id' => ['required', 'ulid', 'distinct:ignore_case'],
            'lines.*.quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']])->validate();

        return DB::transaction(function () use ($data, $input, $id, $demoKey): string {
            if ($id !== null) {
                $draft = $this->drafts->lock($id);
                $this->assertEditable($draft, $input['version'] ?? null);
                if ($draft['invoice_id'] !== strtolower($data['invoice_id'])) {
                    throw ValidationException::withMessages(['invoice_id' => 'Invoice asal tidak boleh diganti.']);
                }
            }
            $invoice = $this->invoices->find($data['invoice_id']);
            if ($invoice === null) {
                throw ValidationException::withMessages(['invoice_id' => 'Invoice posted tidak ditemukan.']);
            }
            $sourceLines = array_column($invoice->lines, null, 'id');
            $lines = [];
            foreach ($data['lines'] as $index => $line) {
                $source = $sourceLines[strtolower($line['invoice_line_id'])] ?? null;
                if ($source === null) {
                    throw ValidationException::withMessages(["lines.{$index}.invoice_line_id" => 'Baris bukan bagian dari invoice asal.']);
                }
                try {
                    $quantity = ReturnQuantity::from($line['quantity'], $source['quantity'], $source['conversion_ratio']);
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(["lines.{$index}.quantity" => $e->getMessage()]);
                }
                $lines[] = ['invoice_line_id' => $source['id'], 'quantity' => $quantity->quantity,
                    'base_quantity' => $quantity->baseQuantity, 'snapshot' => $source];
            }
            usort($lines, fn ($a, $b) => strcmp($a['invoice_line_id'], $b['invoice_line_id']));
            $header = ['invoice_id' => $invoice->header['id'], 'invoice_number' => $invoice->header['number'],
                'customer_name' => $invoice->header['customer_name'], 'snapshot' => $invoice->header,
                'notes' => trim($data['notes'] ?? '') ?: null];
            if ($demoKey !== null && $id === null) {
                $header['demo_key'] = Validator::make(['key' => $demoKey], ['key' => ['required', 'string', 'max:64']])->validate()['key'];
            }

            return $this->drafts->save($header, $lines, $id);
        }, 3);
    }

    public function lifecycle(string $id, mixed $version, bool $restore): void
    {
        DB::transaction(function () use ($id, $version, $restore): void {
            $this->assertEditable($this->drafts->lock($id, $restore), $version);
            $this->drafts->lifecycle($id, $restore);
        }, 3);
    }

    private function assertEditable(array $draft, mixed $version): void
    {
        $validated = Validator::make(['version' => $version], ['version' => ['required', 'integer', 'min:1', 'max:2147483646']])->validate();
        if ($draft['status'] !== 'draft') {
            throw ValidationException::withMessages(['draft' => 'Hanya draft yang boleh diubah.']);
        }
        if ($draft['version'] !== (int) $validated['version']) {
            throw ValidationException::withMessages(['version' => 'Dokumen telah berubah. Muat ulang sebelum melanjutkan.']);
        }
    }
}
