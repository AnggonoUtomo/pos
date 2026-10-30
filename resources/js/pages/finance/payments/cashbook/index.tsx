import InputError from '@/components/input-error';
import { TablePagination } from '@/components/shared/table-pagination';
import { tablePageParams } from '@/components/shared/table-pagination-model';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CirclePlus, Landmark, RotateCcw, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { OperationDialog } from './components/operation-dialog';
import { StatementTable } from './components/statement-table';
import { useCashbookMutation } from './hooks/use-cashbook-mutation';
import { money } from './lib/money';
import type { CashbookProps, CashDialog, CashFilters } from './types';

export default function Cashbook({ records, accounts, filters, today }: CashbookProps) {
    const { can, user } = usePermission();
    const [draft, setDraft] = useState<CashFilters>(filters);
    const [dialog, setDialog] = useState<CashDialog | null>(null);
    const [filterBusy, setFilterBusy] = useState(false);
    const [filterErrors, setFilterErrors] = useState<Record<string, string>>({});
    useEffect(() => setDraft(filters), [filters]);
    const reload = useCallback(() => {
        setDialog(null);
        router.reload({ only: ['records', 'accounts'] });
    }, []);
    const mutation = useCashbookMutation(user?.id, reload);
    const locked = !mutation.ready || mutation.busy || Boolean(mutation.pending) || filterBusy;
    const openDialog = useCallback(
        (next: CashDialog) => {
            if (!locked) {
                mutation.clearErrors();
                setDialog(next);
            }
        },
        [locked, mutation],
    );
    const visit = useCallback((values: CashFilters, page = 1) => {
        router.get(
            route('finance.cashbook.index'),
            { ...tablePageParams(), ...values, page },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => {
                    setFilterBusy(true);
                    setFilterErrors({});
                },
                onFinish: () => setFilterBusy(false),
                onError: (errors) => {
                    setFilterErrors(errors);
                    toast.error('Periksa filter Buku Kas.');
                },
            },
        );
    }, []);
    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (event.altKey && event.key.toLowerCase() === 'f') {
                event.preventDefault();
                document.getElementById('cashbook-account')?.focus();
            }
            if (event.altKey && event.key.toLowerCase() === 'n' && can('finance.cashbook.manage')) {
                event.preventDefault();
                openDialog({ action: 'entry' });
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !filterBusy) {
                event.preventDefault();
                visit(draft);
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [can, openDialog, filterBusy, visit, draft]);
    return (
        <AppLayout breadcrumbs={[{ title: 'Buku Kas', href: route('finance.cashbook.index') }]}>
            <Head title="Buku Kas" />
            <WorkspaceShortcutDropdown
                title="Buku Kas"
                description={records.account?.name ?? 'Kas dan bank'}
                shortcuts={[
                    { label: 'Pilih akun', description: 'Fokus akun kas/bank', keys: ['Alt', 'F'] },
                    { label: 'Catat mutasi', description: 'Mutasi operasional', keys: ['Alt', 'N'], disabled: !can('finance.cashbook.manage') },
                    { label: 'Terapkan filter', description: 'Perbarui mutasi', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup dialog', description: 'Batal', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-5 p-4 lg:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Buku Kas</h1>
                    <div className="flex flex-wrap gap-2">
                        {can('finance.cashbook.open') && (
                            <Button
                                variant="outline"
                                disabled={locked || !accounts.some((account) => account.is_active && !account.starts_on)}
                                onClick={() => openDialog({ action: 'opening' })}
                            >
                                <Landmark className="text-emerald-600 dark:text-emerald-300" />
                                Saldo awal
                            </Button>
                        )}
                        {can('finance.cashbook.manage') && (
                            <Button
                                disabled={locked || !records.opening || !records.account?.is_active}
                                onClick={() => openDialog({ action: 'entry' })}
                            >
                                <CirclePlus />
                                Catat mutasi
                            </Button>
                        )}
                    </div>
                </div>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        visit(draft);
                    }}
                    className="workspace-filters grid items-end gap-3 md:grid-cols-2 xl:grid-cols-[minmax(12rem,2fr)_1fr_1fr_auto]"
                >
                    <div className="min-w-0 space-y-2">
                        <Label htmlFor="cashbook-account">Akun kas/bank</Label>
                        <Select
                            value={draft.account_id ?? ''}
                            onValueChange={(account_id) => setDraft({ ...draft, account_id })}
                            disabled={filterBusy}
                        >
                            <SelectTrigger id="cashbook-account" className="w-full [&>span]:truncate">
                                <SelectValue placeholder="Pilih akun" />
                            </SelectTrigger>
                            <SelectContent>
                                {accounts.map((account) => (
                                    <SelectItem key={account.id} value={account.id}>
                                        {account.code} - {account.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={filterErrors.account_id} />
                    </div>
                    {(['date_from', 'date_to'] as const).map((field) => (
                        <div key={field} className="space-y-2">
                            <Label htmlFor={`cashbook-${field}`}>{field === 'date_from' ? 'Dari tanggal' : 'Sampai tanggal'}</Label>
                            <Input
                                id={`cashbook-${field}`}
                                type="date"
                                min="1900-01-01"
                                max={today}
                                value={draft[field] ?? ''}
                                onChange={(event) => setDraft({ ...draft, [field]: event.target.value || undefined })}
                                disabled={filterBusy}
                            />
                            <InputError message={filterErrors[field]} />
                        </div>
                    ))}
                    <Button variant="outline" type="submit" disabled={filterBusy}>
                        <Search className="text-sky-600 dark:text-sky-300" />
                        Tampilkan
                    </Button>
                </form>
                {mutation.error && !dialog && (
                    <div role="alert" className="space-y-2 rounded-md border border-amber-500/40 p-3 text-sm">
                        <p>{mutation.error}</p>
                        {mutation.pending && (
                            <Button variant="outline" disabled={mutation.busy} onClick={() => void mutation.submit(mutation.pending!)}>
                                <RotateCcw className="text-amber-600" />
                                Ulangi pengiriman yang sama
                            </Button>
                        )}
                    </div>
                )}
                {mutation.pending && dialog && (
                    <div role="status" className="text-amber-700 dark:text-amber-300">
                        {mutation.busy ? 'Mencatat...' : 'Status belum terkonfirmasi. Tutup dialog lalu ulangi pengiriman yang sama.'}
                    </div>
                )}
                {records.account && !records.opening && (
                    <p role="status" className="border-l-2 border-amber-500 pl-3 text-sm text-amber-700 dark:text-amber-300">
                        Saldo awal akun ini belum ditetapkan.
                    </p>
                )}
                {records.opening && (
                    <p className="text-muted-foreground text-sm">
                        Mulai {records.opening.starts_on} (WIB) · Saldo awal {money(records.opening.amount)}
                    </p>
                )}
                {records.summary && (
                    <>
                        <dl className="grid grid-cols-2 gap-4 border-y border-[var(--app-border)] py-4 xl:grid-cols-4">
                            {(
                                [
                                    ['Saldo sebelum periode', records.summary.opening],
                                    ['Masuk periode ini', records.summary.incoming],
                                    ['Keluar periode ini', records.summary.outgoing],
                                    ['Saldo akhir', records.summary.closing],
                                ] as const
                            ).map(([label, value]) => (
                                <div key={label} className="min-w-0">
                                    <dt className="text-muted-foreground text-xs">{label}</dt>
                                    <dd
                                        data-testid={label === 'Saldo akhir' ? 'cashbook-closing' : undefined}
                                        className="mt-1 text-lg font-semibold break-words"
                                    >
                                        {money(value)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        {records.summary.negative && (
                            <p role="alert" className="flex items-start gap-2 text-sm text-rose-700 dark:text-rose-300">
                                <AlertTriangle className="size-4 shrink-0" />
                                Saldo tercatat minus. Periksa saldo awal dan kelengkapan mutasi.
                            </p>
                        )}
                        <StatementTable
                            rows={records.rows}
                            canReverse={can('finance.cashbook.reverse')}
                            disabled={locked}
                            onReverse={(row) => openDialog({ action: 'reverse', sourceId: row.reversible_id!, reference: row.number })}
                        />
                        <TablePagination meta={records.meta} disabled={filterBusy} />
                    </>
                )}
            </section>
            {dialog && (
                <OperationDialog
                    operation={dialog}
                    accounts={accounts}
                    accountId={records.account?.id ?? ''}
                    today={today}
                    locked={Boolean(mutation.pending)}
                    busy={mutation.busy}
                    error={mutation.error}
                    fields={mutation.fields}
                    onClose={() => setDialog(null)}
                    onSubmit={(attempt) => void mutation.submit(attempt)}
                />
            )}
        </AppLayout>
    );
}
