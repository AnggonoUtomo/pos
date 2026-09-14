import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tags, Warehouse } from 'lucide-react';
import { type CustomerLevelOption, type WarehouseOption } from '../types';

type SaleContextBarProps = {
    warehouses: WarehouseOption[];
    customerLevels: CustomerLevelOption[];
    warehouseId: string;
    customerLevelId: string;
    onWarehouseChange: (value: string) => void;
    onCustomerLevelChange: (value: string) => void;
};

export function SaleContextBar({
    warehouses,
    customerLevels,
    warehouseId,
    customerLevelId,
    onWarehouseChange,
    onCustomerLevelChange,
}: SaleContextBarProps) {
    return (
        <div className="flex flex-wrap items-end gap-3">
            <div className="w-52 space-y-1.5">
                <Label htmlFor="warehouse" className="flex items-center gap-1.5">
                    <Warehouse className="size-3.5 text-[var(--chart-2)]" />
                    Gudang
                </Label>
                <Select value={warehouseId} onValueChange={onWarehouseChange}>
                    <SelectTrigger id="warehouse">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {warehouses.map((warehouse) => (
                            <SelectItem key={warehouse.id} value={warehouse.id}>
                                {warehouse.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="w-48 space-y-1.5">
                <Label htmlFor="customer-level" className="flex items-center gap-1.5">
                    <Tags className="size-3.5 text-[var(--chart-1)]" />
                    Level Pelanggan
                </Label>
                <Select value={customerLevelId} onValueChange={onCustomerLevelChange}>
                    <SelectTrigger id="customer-level">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {customerLevels.map((level) => (
                            <SelectItem key={level.id} value={level.id}>
                                {level.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <Badge variant="secondary" className="text-primary mb-1 rounded-md border border-[var(--app-border)] bg-[var(--app-panel-strong)]">
                Mock UI
            </Badge>
        </div>
    );
}
