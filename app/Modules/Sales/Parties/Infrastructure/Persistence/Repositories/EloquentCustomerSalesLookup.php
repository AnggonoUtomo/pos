<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Parties\Application\Contracts\CustomerSalesLookup;
use App\Modules\Sales\Parties\Application\DTOs\SaleCustomerData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class EloquentCustomerSalesLookup implements CustomerSalesLookup
{
    private function query(): Builder
    {
        return Customer::where('is_active', true)->whereHas('level', fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))
            ->with('level:id,name')->select(['id', 'code', 'name', 'customer_level_id']);
    }

    public function choices(): array
    {
        return $this->query()->orderBy('code')->orderBy('id')->limit(1000)->get()->map(fn ($row) => $this->data($row))->all();
    }

    public function findActive(string $id): ?SaleCustomerData
    {
        $row = $this->query()->whereKey($id)->first();

        return $row === null ? null : $this->data($row);
    }

    private function data(Customer $row): SaleCustomerData
    {
        return new SaleCustomerData($row->id, $row->code, $row->name, $row->customer_level_id, $row->level->name);
    }
}
