import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Users, Warehouse } from 'lucide-react';
import type { CustomerOption, WarehouseOption } from '../types';

export function SaleContextBar({
    warehouses,
    customers,
    warehouseId,
    customerId,
    onWarehouseChange,
    onCustomerChange,
    activeCustomer,
}: {
    warehouses: WarehouseOption[];
    customers: CustomerOption[];
    warehouseId: string;
    customerId: string;
    onWarehouseChange: (id: string) => void;
    onCustomerChange: (id: string) => void;
    activeCustomer?: CustomerOption;
}) {
    const customer = activeCustomer?.id === customerId ? activeCustomer : customers.find((row) => row.id === customerId);
    return (
        <div className="flex flex-wrap items-end gap-3 border-b border-[var(--app-border)] bg-[var(--app-panel)] px-5 py-3">
            <div className="w-56 space-y-1.5">
                <Label htmlFor="pos-warehouse" className="flex items-center gap-1.5">
                    <Warehouse className="size-4 text-emerald-600 dark:text-emerald-300" />
                    Gudang
                </Label>
                <Select value={warehouseId} onValueChange={onWarehouseChange}>
                    <SelectTrigger id="pos-warehouse">
                        <SelectValue placeholder="Pilih gudang" />
                    </SelectTrigger>
                    <SelectContent>
                        {warehouses.map((row) => (
                            <SelectItem key={row.id} value={row.id}>
                                {row.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="w-72 space-y-1.5">
                <Label htmlFor="pos-customer" className="flex items-center gap-1.5">
                    <Users className="size-4 text-sky-600 dark:text-sky-300" />
                    Pelanggan
                </Label>
                <Select value={customerId} onValueChange={onCustomerChange}>
                    <SelectTrigger id="pos-customer">
                        <SelectValue placeholder="Pilih pelanggan" />
                    </SelectTrigger>
                    <SelectContent>
                        {customers.map((row) => (
                            <SelectItem key={row.id} value={row.id}>
                                {row.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {customer && (
                <Badge
                    className="mb-1 border-violet-200 bg-violet-500/10 text-violet-700 dark:border-violet-900 dark:text-violet-300"
                    variant="outline"
                >
                    {customer.levelName}
                </Badge>
            )}
            {warehouses.length === 0 && (
                <p role="status" className="text-destructive text-sm">
                    Belum ada gudang aktif.
                </p>
            )}
            {customers.length === 0 && (
                <p role="status" className="text-destructive text-sm">
                    Belum ada pelanggan aktif.
                </p>
            )}
        </div>
    );
}
