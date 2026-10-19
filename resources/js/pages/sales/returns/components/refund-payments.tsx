import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { parseDecimalInput as parse } from '@/lib/decimal-input';
import { Plus, Trash2 } from 'lucide-react';
import type { RefundPayment, RefundQuote } from '../types';

export const cents = (value: string): bigint => {
    const [whole, fraction = ''] = value.split('.');
    return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
};
export const money = (value: bigint) => `${value / 100n}.${(value % 100n).toString().padStart(2, '0')}`;
export function canonical(value: string): string | null {
    const parsed = parse(value, 2);
    return parsed !== null && /^\d{1,28}(?:\.\d{1,2})?$/.test(parsed) && cents(parsed) > 0n ? money(cents(parsed)) : null;
}
export function RefundPayments({
    quote,
    rows,
    onChange,
    disabled,
}: {
    quote: RefundQuote;
    rows: RefundPayment[];
    onChange: (rows: RefundPayment[]) => void;
    disabled: boolean;
}) {
    const patch = (index: number, value: Partial<RefundPayment>) => onChange(rows.map((row, i) => (i === index ? { ...row, ...value } : row)));
    return (
        <fieldset disabled={disabled} className="min-w-0 space-y-3">
            <legend className="mb-3 text-sm font-medium">Pengembalian dana</legend>
            {quote.methods.length === 0 && (
                <p role="alert" className="text-destructive text-sm">
                    Tidak ada metode pembayaran aktif.
                </p>
            )}
            {rows.map((row, index) => (
                <div key={index} className="grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_2.25rem] items-start gap-2">
                    <div className="min-w-0 space-y-2">
                        <Label htmlFor={`refund-method-${index}`}>Metode {index + 1}</Label>
                        <Select value={row.method_id || undefined} onValueChange={(method_id) => patch(index, { method_id })} disabled={disabled}>
                            <SelectTrigger id={`refund-method-${index}`} className="w-full min-w-0">
                                <SelectValue placeholder="Pilih metode" />
                            </SelectTrigger>
                            <SelectContent>
                                {quote.methods.map((method) => (
                                    <SelectItem
                                        key={method.id}
                                        value={method.id}
                                        disabled={rows.some((other, i) => i !== index && other.method_id === method.id)}
                                    >
                                        {method.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="min-w-0 space-y-2">
                        <Label htmlFor={`refund-amount-${index}`}>Jumlah {index + 1}</Label>
                        <Input
                            id={`refund-amount-${index}`}
                            inputMode="decimal"
                            maxLength={42}
                            value={row.amount}
                            onChange={(e) => patch(index, { amount: e.target.value })}
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="mt-6"
                        aria-label={`Hapus metode ${index + 1}`}
                        title={`Hapus metode ${index + 1}`}
                        disabled={disabled || rows.length === 1}
                        onClick={() => onChange(rows.filter((_, i) => i !== index))}
                    >
                        <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                    </Button>
                </div>
            ))}
            <Button
                type="button"
                size="icon"
                variant="outline"
                aria-label="Tambah metode refund"
                title="Tambah metode refund"
                disabled={disabled || rows.length >= Math.min(10, quote.methods.length)}
                onClick={() => onChange([...rows, { method_id: '', amount: '' }])}
            >
                <Plus className="size-4 text-sky-600 dark:text-sky-300" />
            </Button>
        </fieldset>
    );
}
