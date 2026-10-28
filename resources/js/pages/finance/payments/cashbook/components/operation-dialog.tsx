import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCheck, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { parseMoney } from '../lib/money';
import type { Attempt, CashAccount, CashDialog } from '../types';

export function OperationDialog({
    operation,
    accounts,
    accountId,
    today,
    locked,
    busy,
    error,
    fields,
    onClose,
    onSubmit,
}: {
    operation: CashDialog;
    accounts: CashAccount[];
    accountId: string;
    today: string;
    locked: boolean;
    busy: boolean;
    error: string;
    fields: Record<string, string>;
    onClose: () => void;
    onSubmit: (attempt: Attempt) => void;
}) {
    const [primary, setPrimary] = useState(accountId);
    const [counter, setCounter] = useState('');
    const [kind, setKind] = useState('incoming');
    const [amount, setAmount] = useState('0');
    const [date, setDate] = useState(today);
    const [description, setDescription] = useState('');
    const [key] = useState(() => crypto.randomUUID());
    const [localError, setLocalError] = useState('');
    const title = operation.action === 'opening' ? 'Tetapkan saldo awal' : operation.action === 'reverse' ? 'Balik mutasi' : 'Catat mutasi';
    const choices = accounts.filter(
        (account) => account.is_active && (operation.action === 'opening' ? !account.starts_on : Boolean(account.starts_on)),
    );
    const submit = () => {
        setLocalError('');
        if (operation.action === 'reverse') {
            onSubmit({ requestId: key, action: 'reverse', sourceId: operation.sourceId, data: { reason: description } });
            return;
        }
        const parsed = parseMoney(amount);
        if (parsed === null) {
            setLocalError('Gunakan format Indonesia, misalnya 1.250,5.');
            return;
        }
        onSubmit({
            requestId: key,
            action: operation.action,
            data:
                operation.action === 'opening'
                    ? { account_id: primary, amount: parsed, starts_on: date }
                    : {
                          account_id: primary,
                          counter_account_id: kind === 'transfer' ? counter : null,
                          kind,
                          amount: parsed,
                          occurred_on: date,
                          description,
                          idempotency_key: key,
                      },
        });
    };
    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !busy) onClose();
            }}
        >
            <DialogContent className="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {operation.reference ?? 'Buku Kas'}
                        {operation.action === 'opening' ? ' · Posisi sebelum transaksi tanggal mulai' : ''}
                    </DialogDescription>
                </DialogHeader>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="space-y-4"
                >
                    <fieldset disabled={locked || busy} className="min-w-0 space-y-4">
                        {operation.action !== 'reverse' && (
                            <>
                                {operation.action === 'entry' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="cash-kind">Jenis mutasi</Label>
                                        <Select value={kind} onValueChange={setKind}>
                                            <SelectTrigger id="cash-kind">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="incoming">Pemasukan operasional</SelectItem>
                                                <SelectItem value="outgoing">Pengeluaran operasional</SelectItem>
                                                <SelectItem value="transfer">Transfer antar-akun</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={fields.kind} />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <Label htmlFor="cash-primary">Akun {kind === 'transfer' && operation.action === 'entry' ? 'asal' : ''}</Label>
                                    <Select value={primary} onValueChange={setPrimary}>
                                        <SelectTrigger id="cash-primary">
                                            <SelectValue placeholder="Pilih akun" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {choices.map((account) => (
                                                <SelectItem key={account.id} value={account.id}>
                                                    {account.code} - {account.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={fields.account_id} />
                                </div>
                                {kind === 'transfer' && operation.action === 'entry' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="cash-counter">Akun tujuan</Label>
                                        <Select value={counter} onValueChange={setCounter}>
                                            <SelectTrigger id="cash-counter">
                                                <SelectValue placeholder="Pilih akun tujuan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {choices
                                                    .filter((account) => account.id !== primary)
                                                    .map((account) => (
                                                        <SelectItem key={account.id} value={account.id}>
                                                            {account.code} - {account.name}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={fields.counter_account_id} />
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="cash-amount">Nominal (Rp)</Label>
                                        <Input
                                            id="cash-amount"
                                            value={amount}
                                            inputMode="decimal"
                                            maxLength={40}
                                            onChange={(event) => setAmount(event.target.value)}
                                        />
                                        <InputError message={fields.amount ?? localError} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="cash-date">{operation.action === 'opening' ? 'Tanggal mulai' : 'Tanggal mutasi'}</Label>
                                        <Input
                                            id="cash-date"
                                            type="date"
                                            value={date}
                                            min={
                                                operation.action === 'entry'
                                                    ? (choices.find((account) => account.id === primary)?.starts_on ?? '1900-01-01')
                                                    : '1900-01-01'
                                            }
                                            max={today}
                                            onChange={(event) => setDate(event.target.value)}
                                        />
                                        <InputError message={fields.starts_on ?? fields.occurred_on} />
                                    </div>
                                </div>
                            </>
                        )}
                        {operation.action !== 'opening' && (
                            <div className="space-y-2">
                                <Label htmlFor="cash-description">{operation.action === 'reverse' ? 'Alasan pembalikan' : 'Keterangan'}</Label>
                                <Input
                                    id="cash-description"
                                    value={description}
                                    maxLength={200}
                                    onChange={(event) => setDescription(event.target.value)}
                                    required
                                />
                                <InputError message={fields.description ?? fields.reason} />
                            </div>
                        )}
                    </fieldset>
                    {error && (
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={locked || busy}>
                            {busy ? <LoaderCircle className="animate-spin" /> : <CheckCheck />} {title}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
