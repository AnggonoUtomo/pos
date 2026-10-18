<?php

namespace App\Modules\Sales\POS\Application\DTOs;

final readonly class ReturnInvoiceData
{
    /** @param list<array<string, string|int>> $lines */
    public function __construct(public array $header, public array $lines) {}

    public function toArray(): array
    {
        return $this->header + ['lines' => $this->lines];
    }
}
