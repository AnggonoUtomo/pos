import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { Users, Warehouse } from 'lucide-react';
import type { CustomerOption, WarehouseOption } from '../types';
import { CustomerAutocomplete } from './customer-autocomplete';

export function SaleContextBar({
    warehouses,
    customers,
    warehouseId,
    customerId,
    onWarehouseChange,
    onCustomerChange,
    activeCustomer,
    compact = false,
    disabled = false,
}: {
    warehouses: WarehouseOption[];
    customers: CustomerOption[];
    warehouseId: string;
    customerId: string;
    onWarehouseChange: (id: string) => void;
    onCustomerChange: (id: string) => void;
    activeCustomer?: CustomerOption;
    compact?: boolean;
    disabled?: boolean;
}) {
    const customer = activeCustomer?.id === customerId ? activeCustomer : customers.find((row) => row.id === customerId);
    return (
        <div className={cn('items-end gap-3 bg-[var(--app-panel)] px-4 py-2', compact ? 'grid grid-cols-2' : 'flex flex-wrap')}>
            <div className={cn('min-w-0 space-y-1.5', compact ? '' : 'w-56')}>
                <Label htmlFor="pos-warehouse" className="flex items-center gap-1.5">
                    <Warehouse className="size-4 text-emerald-600 dark:text-emerald-300" />
                    Gudang
                </Label>
                <Select value={warehouseId} onValueChange={onWarehouseChange}>
                    <SelectTrigger id="pos-warehouse" className="w-full min-w-0 [&>span]:truncate">
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
            <div className={cn('min-w-0 space-y-1.5', compact ? '' : 'w-72')}>
                <div className="flex min-w-0 items-center justify-between gap-2">
                    <Label htmlFor="pos-customer" className="flex items-center gap-1.5">
                        <Users className="size-4 text-sky-600 dark:text-sky-300" />
                        Pelanggan
                    </Label>
                    {compact && customer && <span className="truncate text-xs text-violet-600 dark:text-violet-300">{customer.levelName}</span>}
                </div>
                <CustomerAutocomplete customers={customers} customer={customer} disabled={disabled} onChange={onCustomerChange} />
            </div>
            {customer && !compact && (
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
