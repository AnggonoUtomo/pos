import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import type { ItemPriceInput, ItemUnitInput, PriceLevel, UnitOption } from '../types';

export function priceMatrix(units: ItemUnitInput[], levels: PriceLevel[], prices: ItemPriceInput[]): ItemPriceInput[] {
    const amounts = new Map(prices.map((price) => [`${price.unit_id}:${price.customer_level_id}`, price.amount]));
    return units
        .filter((unit) => unit.is_sale_unit && unit.unit_id)
        .flatMap((unit) =>
            levels.map((level) => ({
                unit_id: unit.unit_id,
                customer_level_id: level.id,
                amount: amounts.get(`${unit.unit_id}:${level.id}`) ?? '',
            })),
        );
}

export function PriceEditor({
    units,
    levels,
    prices,
    options,
    errors,
    disabled,
    onChange,
}: {
    units: ItemUnitInput[];
    levels: PriceLevel[];
    prices: ItemPriceInput[];
    options: UnitOption[];
    errors: Record<string, string>;
    disabled: boolean;
    onChange: (rows: ItemPriceInput[]) => void;
}) {
    const matrix = priceMatrix(units, levels, prices);
    const saleUnits = units.filter((unit) => unit.is_sale_unit && unit.unit_id);
    const labels = new Map(options.map((unit) => [unit.id, unit.name]));
    return (
        <div className="space-y-3">
            <h3 className="text-sm font-semibold">Harga jual (Rp)</h3>
            {saleUnits.length > 0 && levels.length === 0 && (
                <p className="text-destructive text-sm" role="alert">
                    Belum ada level pelanggan aktif.
                </p>
            )}
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/40 text-muted-foreground">
                        <tr>
                            <th scope="col" className="px-3 py-2 text-left font-medium">
                                Satuan jual
                            </th>
                            {levels.map((level) => (
                                <th key={level.id} scope="col" className="min-w-36 px-3 py-2 text-left font-medium">
                                    {level.name}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {saleUnits.length === 0 ? (
                            <tr>
                                <td colSpan={levels.length + 1} className="text-muted-foreground px-3 py-4">
                                    Tidak ada satuan jual.
                                </td>
                            </tr>
                        ) : (
                            saleUnits.map((unit, row) => (
                                <tr key={unit.unit_id}>
                                    <th scope="row" className="min-w-32 px-3 py-3 text-left align-top font-medium">
                                        {labels.get(unit.unit_id) ?? '-'}
                                    </th>
                                    {levels.map((level, col) => {
                                        const index = row * levels.length + col;
                                        return (
                                            <td key={level.id} className="min-w-36 px-3 py-3 align-top">
                                                <Input
                                                    aria-label={`Harga ${labels.get(unit.unit_id) ?? 'satuan'} ${level.name}`}
                                                    inputMode="decimal"
                                                    required
                                                    maxLength={27}
                                                    disabled={disabled}
                                                    value={matrix[index].amount}
                                                    aria-invalid={Boolean(errors[`prices.${index}.amount`])}
                                                    onChange={(e) =>
                                                        onChange(
                                                            matrix.map((price, i) => (i === index ? { ...price, amount: e.target.value } : price)),
                                                        )
                                                    }
                                                />
                                                <InputError message={errors[`prices.${index}.amount`]} />
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
            <InputError message={errors.prices} />
        </div>
    );
}
