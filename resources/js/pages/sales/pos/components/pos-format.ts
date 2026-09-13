import { type CartLine, type CustomerLevelOption, type UnitOption } from '../types';

export function money(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

export function selectedUnit(line: CartLine): UnitOption {
    return line.item.units.find((unit) => unit.code === line.unitCode) ?? line.item.units[0];
}

export function levelPrice(unit: UnitOption, level: CustomerLevelOption): number {
    return Math.round(unit.price * level.priceModifier);
}

export function lineSubtotal(line: CartLine, level: CustomerLevelOption): number {
    return Math.max(0, line.qty * levelPrice(selectedUnit(line), level) - line.discount);
}
