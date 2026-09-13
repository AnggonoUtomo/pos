<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Controllers;

use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use App\Modules\HumanResource\HumanResource\Application\DTO\PaginatedEmployeeData;
use App\Modules\HumanResource\HumanResource\Application\Queries\ListEmployees;
use App\Modules\HumanResource\HumanResource\Presentation\Requests\ListEmployeesApiRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmployeeController implements HasMiddleware
{
    public function __construct(private ListEmployees $listEmployees) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:human_resource.view', only: ['index']),
        ];
    }

    public function index(ListEmployeesApiRequest $request): Response
    {
        $result = $this->listEmployees->execute($request->toFilter());

        return Inertia::render('HumanResource/HumanResource/pages/Index', [
            'employees' => [
                'data' => array_map(
                    static fn (EmployeeData $employee): array => $employee->toArray(),
                    $result->data,
                ),
                'meta' => $this->paginationMeta($result),
            ],
            'filters' => $request->safe()->only(['search', 'filter', 'page', 'per_page', 'sort']),
            'pagination' => [
                'perPageOptions' => [10, 25, 50, 100],
                'defaultPerPage' => 25,
            ],
        ]);
    }

    /** @return array{currentPage: int, perPage: int, total: int, lastPage: int} */
    private function paginationMeta(PaginatedEmployeeData $result): array
    {
        return [
            'currentPage' => $result->currentPage,
            'perPage' => $result->perPage,
            'total' => $result->total,
            'lastPage' => $result->lastPage,
        ];
    }
}
