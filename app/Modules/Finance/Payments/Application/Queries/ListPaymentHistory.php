<?php

namespace App\Modules\Finance\Payments\Application\Queries;

use App\Modules\Finance\Payments\Application\Contracts\PaymentHistoryReader;
use Carbon\CarbonImmutable;

class ListPaymentHistory
{
    public function __construct(private PaymentHistoryReader $reader) {}

    public function records(int $page, array $filters): array
    {
        foreach (['date_from' => 'from', 'date_to' => 'until'] as $key => $boundary) {
            $date = $filters[$key] ? CarbonImmutable::createFromFormat('!Y-m-d', $filters[$key], 'Asia/Jakarta') : null;
            if ($date !== null && $boundary === 'until') {
                $date = $date->addDay();
            }
            $filters[$boundary] = $date?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }

        return $this->reader->paginate($page, $filters);
    }

    public function detail(string $direction, string $id): array
    {
        return $this->reader->detail($direction, strtolower($id));
    }

    public function options(): array
    {
        return $this->reader->options();
    }
}
