<?php

namespace App\Modules\Platform\OperationalSettings\Infrastructure\Support;

use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequestTablePagination implements TablePagination
{
    public function __construct(private Request $request, private TablePreferences $preferences) {}

    public function perPage(): int
    {
        $data = Validator::make(['per_page' => $this->request->query('per_page', $this->preferences->defaultPerPage())], [
            'per_page' => ['required', 'integer', Rule::in(TablePreferences::SIZES)],
        ])->validate();

        return (int) $data['per_page'];
    }
}
