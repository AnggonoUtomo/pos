import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import type { AccountOption, PaymentFilters } from '../types';
import { accountTypes, methodTypes } from '../types';

export function TypeFilter({ filters, methods = false, accounts = [] }: { filters: PaymentFilters; methods?: boolean; accounts?: AccountOption[] }) {
    const visit = (patch: Partial<PaymentFilters>) =>
        router.get(
            route(`finance.${methods ? 'payment-methods' : 'payment-accounts'}.index`),
            { ...filters, ...patch, trashed: filters.trashed ? 1 : 0, page: 1 },
            { preserveState: true, preserveScroll: true, onError: () => toast.error('Filter tidak valid.') },
        );
    return (
        <div className="flex flex-wrap items-center gap-3">
            <Label htmlFor="payment-type-filter">Jenis</Label>
            <Select value={filters.type ?? 'all'} onValueChange={(type) => visit({ type: type === 'all' ? null : type })}>
                <SelectTrigger id="payment-type-filter" className="w-40">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua jenis</SelectItem>
                    {Object.entries(methods ? methodTypes : accountTypes).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                            {label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {methods && (
                <>
                    <Label htmlFor="payment-account-filter">Akun</Label>
                    <Select
                        value={filters.payment_account_id ?? 'all'}
                        onValueChange={(id) => visit({ payment_account_id: id === 'all' ? null : id })}
                    >
                        <SelectTrigger id="payment-account-filter" className="w-64">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua akun</SelectItem>
                            {filters.payment_account_id && !accounts.some((account) => account.id === filters.payment_account_id) && (
                                <SelectItem value={filters.payment_account_id}>Akun tidak aktif</SelectItem>
                            )}
                            {accounts.map((account) => (
                                <SelectItem key={account.id} value={account.id}>
                                    {account.code} - {account.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </>
            )}
        </div>
    );
}
