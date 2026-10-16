<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\InvoiceHistoryReader;
use Carbon\CarbonImmutable;

class ListInvoiceHistory
{
    public function __construct(private InvoiceHistoryReader $reader) {}

    public function handle(int $page, array $filters, int $actorId, bool $viewAll): array
    {
        if ($actorId < 1) {
            throw new \InvalidArgumentException('Pemilik riwayat tidak valid.');
        }
        $scope = $viewAll ? null : $actorId;
        $queryFilters = $filters;
        foreach (['date_from' => 'from', 'date_to' => 'until'] as $key => $boundary) {
            $date = $filters[$key] ? CarbonImmutable::createFromFormat('!Y-m-d', $filters[$key], 'Asia/Jakarta') : null;
            if ($date !== null && $boundary === 'until') {
                $date = $date->addDay();
            }
            $queryFilters[$boundary] = $date?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }

        return ['records' => $this->reader->paginate($page, $queryFilters, $scope),
            'filters' => $filters, 'viewAll' => $viewAll] + $this->reader->options($scope);
    }
}
