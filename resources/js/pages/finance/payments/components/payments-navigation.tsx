import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { CreditCard, Landmark } from 'lucide-react';

export function PaymentsNavigation({ methods = false }: { methods?: boolean }) {
    return (
        <nav className="flex flex-wrap gap-2" aria-label="Navigasi master pembayaran">
            <Button size="sm" variant={methods ? 'outline' : 'secondary'} asChild>
                <Link href={route('finance.payment-accounts.index')} aria-current={!methods ? 'page' : undefined}>
                    <Landmark className="size-4 text-emerald-600 dark:text-emerald-300" />
                    Kas dan bank
                </Link>
            </Button>
            <Button size="sm" variant={methods ? 'secondary' : 'outline'} asChild>
                <Link href={route('finance.payment-methods.index')} aria-current={methods ? 'page' : undefined}>
                    <CreditCard className="size-4 text-violet-600 dark:text-violet-300" />
                    Metode pembayaran
                </Link>
            </Button>
        </nav>
    );
}
