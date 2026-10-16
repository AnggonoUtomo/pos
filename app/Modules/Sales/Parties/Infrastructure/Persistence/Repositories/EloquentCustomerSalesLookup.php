<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Parties\Application\Contracts\CustomerSalesLookup;
use App\Modules\Sales\Parties\Application\DTOs\SaleCustomerData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\Customer;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

    public function findActive(string $id, bool $lock = false): ?SaleCustomerData
    {
        if ($lock) {
            if (DB::transactionLevel() === 0) {
                throw new \LogicException('Pelanggan lock memerlukan transaction default.');
            }
            $discovery = Customer::whereKey($id)->first(['id', 'customer_level_id']);
            if ($discovery === null) {
                return null;
            }
            $level = CustomerLevel::whereKey($discovery->customer_level_id)->where('is_active', true)->lockForUpdate()->first(['id', 'name']);
            $row = Customer::whereKey($id)->where('is_active', true)->lockForUpdate()->first(['id', 'code', 'name', 'customer_level_id']);
            if ($level === null || $row === null || $row->customer_level_id !== $level->id) {
                return null;
            }
            $row->setRelation('level', $level);

            return $this->data($row);
        }
        $row = $this->query()->whereKey($id)->first();

        return $row === null ? null : $this->data($row);
    }

    private function data(Customer $row): SaleCustomerData
    {
        return new SaleCustomerData($row->id, $row->code, $row->name, $row->customer_level_id, $row->level->name);
    }
}
