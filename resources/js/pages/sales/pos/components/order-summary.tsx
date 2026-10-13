import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CheckCheck, CreditCard, LoaderCircle } from 'lucide-react';
import { money } from '../lib/decimal';
import type { CartQuote } from '../types';

export function OrderSummary({
    quote,
    loading,
    disabled,
    error,
    onCheck,
}: {
    quote: CartQuote | null;
    loading: boolean;
    disabled: boolean;
    error: string;
    onCheck: () => void;
}) {
    return (
        <section className="space-y-3 border-t border-[var(--app-border)] bg-[var(--app-panel-strong)] p-4">
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
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Badge variant="outline" className={quote ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}>
                    {quote ? 'Terperiksa' : 'Belum diperiksa'}
                </Badge>
                <span className="text-muted-foreground text-xs">Belum diposting</span>
            </div>
            <div className="grid grid-cols-2 gap-2">
                <Button onClick={onCheck} disabled={disabled || loading}>
                    {loading ? <LoaderCircle className="size-4 animate-spin" /> : <CheckCheck className="size-4" />}Periksa keranjang
                </Button>
                <span title="Posting dan pembayaran belum tersedia">
                    <Button className="w-full" variant="outline" disabled>
                        <CreditCard className="size-4" />
                        Pembayaran
                    </Button>
                </span>
            </div>
        </section>
    );
}
