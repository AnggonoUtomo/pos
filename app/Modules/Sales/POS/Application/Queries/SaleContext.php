<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Sales\Parties\Application\Contracts\CustomerSalesLookup;
use Illuminate\Validation\ValidationException;

class SaleContext
{
    public function __construct(private WarehouseLookup $warehouses, private CustomerSalesLookup $customers) {}

    public function options(): array
    {
        return ['warehouses' => $this->warehouses->choices(), 'customers' => $this->customers->choices()];
    }

    public function resolve(string $warehouseId, string $customerId, bool $lock = false): array
    {
        $warehouse = $this->warehouses->findActive($warehouseId, $lock);
        $customer = $this->customers->findActive($customerId, $lock);
        if ($warehouse === null) {
            throw ValidationException::withMessages(['warehouse_id' => 'Pilih gudang aktif yang tersedia.']);
        }
        if ($customer === null) {
            throw ValidationException::withMessages(['customer_id' => 'Pelanggan dan level pelanggan harus aktif.']);
        }

        return ['warehouse' => $warehouse, 'customer' => $customer];
    }
}
