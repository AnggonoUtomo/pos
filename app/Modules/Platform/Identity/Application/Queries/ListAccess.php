<?php

namespace App\Modules\Platform\Identity\Application\Queries;

use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;

class ListAccess
{
    public function __construct(private AccessRepository $access) {}

    public function handle(array $filters): array
    {
        return ['records' => $this->access->page($filters['tab'], $filters['search'], $filters['page']),
            'filters' => $filters, 'options' => $this->access->options()];
    }
}
