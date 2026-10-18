<?php

namespace App\Modules\Sales\POS\Application\DTOs;

final readonly class ReturnRefundBasisData
{
    public function __construct(public array $header, public array $lines) {}
}
