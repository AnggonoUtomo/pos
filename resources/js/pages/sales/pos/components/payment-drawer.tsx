import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Separator } from '@/components/ui/separator';
import { money } from './pos-format';
import { type PaymentLine } from '../types';

type PaymentDrawerProps = {
    open: boolean;
    total: number;
    payments: PaymentLine[];
    onOpenChange: (value: boolean) => void;
    onPaymentChange: (id: string, amount: number) => void;
    onSubmit: () => void;
};

export function PaymentDrawer({ open, total, payments, onOpenChange, onPaymentChange, onSubmit }: PaymentDrawerProps) {
    const paid = payments.reduce((sum, payment) => sum + payment.amount, 0);
    const remaining = Math.max(0, total - paid);
    const change = Math.max(0, paid - total);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-[24rem] sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Pembayaran</SheetTitle>
                    <SheetDescription>Simulasi multi payment untuk mock POS.</SheetDescription>
                </SheetHeader>

                <div className="mt-6 grid gap-4">
                    <div className="rounded-lg border p-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">Total Tagihan</span>
                            <span className="font-semibold">{money(total)}</span>
                        </div>
                        <Separator className="my-3" />
                        <div className="grid gap-3">
                            {payments.map((payment) => (
                                <label key={payment.id} className="grid gap-1.5 text-sm">
                                    <span>{payment.method}</span>
                                    <Input
                                        id={`payment-${payment.id}`}
                                        name={`payment_${payment.id}`}
                                        value={payment.amount}
                                        onChange={(event) => onPaymentChange(payment.id, Number(event.target.value))}
                                        inputMode="numeric"
                                    />
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Dibayar</span>
                            <span>{money(paid)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Sisa</span>
                            <span>{money(remaining)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Kembalian</span>
                            <span>{money(change)}</span>
                        </div>
                    </div>

                    <Button size="lg" onClick={onSubmit} disabled={total <= 0 || paid < total}>
                        Simpan Pembayaran Mock
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
