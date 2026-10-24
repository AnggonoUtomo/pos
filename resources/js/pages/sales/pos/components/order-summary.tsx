import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCheck, CreditCard, LoaderCircle } from 'lucide-react';
import { money } from '../lib/decimal';
import type { CartQuote, InvoicePricing } from '../types';

export function OrderSummary({
    quote,
    pricing,
    onPricing,
    loading,
    disabled,
    error,
    onCheck,
    onPay,
    canPay,
    locked,
}: {
    quote: CartQuote | null;
    pricing: InvoicePricing;
    onPricing: (pricing: InvoicePricing) => void;
    loading: boolean;
    disabled: boolean;
    error: string;
    onCheck: () => void;
    onPay: () => void;
    canPay: boolean;
    locked: boolean;
}) {
    return (
        <section className="space-y-3 border-t border-[var(--app-border)] bg-[var(--app-panel-strong)] p-4">
            <fieldset disabled={locked || loading} className="grid grid-cols-[6.5rem_minmax(0,1fr)_minmax(0,1fr)] items-end gap-2">
                <div className="space-y-1">
                    <Label>Diskon</Label>
                    <Select
                        value={pricing.discount_type}
                        onValueChange={(value) => onPricing({ ...pricing, discount_type: value as 'fixed' | 'percent' })}
                    >
                        <SelectTrigger aria-label="Jenis diskon">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="fixed">Rp</SelectItem>
                            <SelectItem value="percent">%</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-1">
                    <Label htmlFor="pos-discount">Nilai diskon</Label>
                    <Input
                        id="pos-discount"
                        inputMode="decimal"
                        maxLength={40}
                        value={pricing.discount_value}
                        onChange={(e) => onPricing({ ...pricing, discount_value: e.target.value })}
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor="pos-tax">Pajak (%)</Label>
                    <Input
                        id="pos-tax"
                        inputMode="decimal"
                        maxLength={6}
                        value={pricing.tax_rate}
                        onChange={(e) => onPricing({ ...pricing, tax_rate: e.target.value })}
                    />
                </div>
            </fieldset>
            {error && (
                <p role="alert" className="text-destructive text-sm">
                    {error}
                </p>
            )}
            <div className="flex items-center justify-between gap-3">
                <span className="text-sm">Subtotal</span>
                <strong data-testid="cart-subtotal" className="max-w-[70%] text-right text-lg break-all">
                    {quote ? money(quote.subtotal) : '-'}
                </strong>
            </div>
            {quote && (
                <dl className="grid grid-cols-2 gap-1 text-sm">
                    <dt>Diskon</dt>
                    <dd className="text-right break-all">{money(quote.discount_amount)}</dd>
                    <dt>Pajak</dt>
                    <dd className="text-right break-all">{money(quote.tax_amount)}</dd>
                    <dt className="font-semibold">Total</dt>
                    <dd className="text-right font-semibold break-all" data-testid="cart-total">
                        {money(quote.total)}
                    </dd>
                </dl>
            )}
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Badge variant="outline" className={quote ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}>
                    {quote ? 'Terperiksa' : 'Belum diperiksa'}
                </Badge>
                <span className="text-muted-foreground text-xs">Belum diposting</span>
            </div>
            <div className="grid grid-cols-2 gap-2">
                <Button onClick={onCheck} disabled={disabled || loading || locked}>
                    {loading ? <LoaderCircle className="size-4 animate-spin" /> : <CheckCheck className="size-4" />}Periksa keranjang
                </Button>
                <Button className="w-full" variant="outline" onClick={onPay} disabled={!quote || loading || disabled || locked || !canPay}>
                    <CreditCard className="size-4" />
                    Pembayaran
                </Button>
            </div>
        </section>
    );
}
