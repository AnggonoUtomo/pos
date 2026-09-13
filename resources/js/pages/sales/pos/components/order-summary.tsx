import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { CreditCard } from 'lucide-react';
import { money } from './pos-format';

type OrderSummaryProps = {
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    onOpenPayment: () => void;
};

export function OrderSummary({ subtotal, discount, tax, total, onOpenPayment }: OrderSummaryProps) {
    return (
        <section className="rounded-lg border bg-background p-4">
            <div className="grid gap-2 text-sm">
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">Subtotal</span>
                    <span>{money(subtotal)}</span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">Diskon Item</span>
                    <span>{money(discount)}</span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">Pajak 11%</span>
                    <span>{money(tax)}</span>
                </div>
                <Separator className="my-1" />
                <div className="flex items-center justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>{money(total)}</span>
                </div>
            </div>
            <Button className="mt-4 w-full" size="lg" onClick={onOpenPayment} disabled={total <= 0}>
                <CreditCard />
                Pembayaran
            </Button>
        </section>
    );
}
