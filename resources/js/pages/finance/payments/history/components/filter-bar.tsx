import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RotateCcw, Search } from 'lucide-react';
import type { RefObject } from 'react';
import type { HistoryFilters, HistoryOptions } from '../types';

export function FilterBar({
    draft,
    options,
    onChange,
    onApply,
    onReset,
    loading,
    errors,
    searchRef,
}: {
    draft: HistoryFilters;
    options: HistoryOptions;
    onChange: (patch: Partial<HistoryFilters>) => void;
    onApply: () => void;
    onReset: () => void;
    loading: boolean;
    errors: Record<string, string>;
    searchRef: RefObject<HTMLInputElement | null>;
}) {
    return (
        <form
            className="workspace-filters space-y-3"
            onSubmit={(e) => {
                e.preventDefault();
                onApply();
            }}
        >
            <fieldset disabled={loading} className="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <legend className="sr-only">Filter pembayaran</legend>
                <div className="min-w-0 space-y-2">
                    <Label htmlFor="payment-search">Nomor transaksi</Label>
                    <Input
                        id="payment-search"
                        ref={searchRef}
                        value={draft.search}
                        maxLength={150}
                        placeholder="Cari nomor transaksi..."
                        onChange={(e) => onChange({ search: e.target.value })}
                    />
                    <InputError message={errors.search} />
                </div>
                <div className="min-w-0 space-y-2">
                    <Label htmlFor="payment-direction">Arah</Label>
                    <Select
                        value={draft.direction}
                        onValueChange={(direction: HistoryFilters['direction']) => onChange({ direction })}
                        disabled={loading}
                    >
                        <SelectTrigger id="payment-direction" className="w-full min-w-0">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua arah</SelectItem>
                            <SelectItem value="incoming">Kas masuk</SelectItem>
                            <SelectItem value="outgoing">Kas keluar</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.direction} />
                </div>
                {(['account_id', 'method_id'] as const).map((key) => {
                    const label = key === 'account_id' ? 'Akun' : 'Metode';
                    const choices = key === 'account_id' ? options.accounts : options.methods;
                    return (
                        <div key={key} className="min-w-0 space-y-2">
                            <Label htmlFor={`payment-${key}`}>{label}</Label>
                            <Select
                                value={draft[key] || 'all'}
                                onValueChange={(value) => onChange({ [key]: value === 'all' ? null : value })}
                                disabled={loading}
                            >
                                <SelectTrigger id={`payment-${key}`} className="w-full min-w-0">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua {label.toLowerCase()}</SelectItem>
                                    {draft[key] && !choices.some((choice) => choice.id === draft[key]) && (
                                        <SelectItem value={draft[key]!}>Pilihan tidak ditemukan</SelectItem>
                                    )}
                                    {choices.map((choice) => (
                                        <SelectItem key={choice.id} value={choice.id}>
                                            {choice.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors[key]} />
                        </div>
                    );
                })}
                {(['date_from', 'date_to'] as const).map((key) => (
                    <div key={key} className="min-w-0 space-y-2">
                        <Label htmlFor={`payment-${key}`}>{key === 'date_from' ? 'Dari tanggal' : 'Sampai tanggal'}</Label>
                        <Input
                            id={`payment-${key}`}
                            type="date"
                            min="1900-01-01"
                            max="9998-12-31"
                            value={draft[key] ?? ''}
                            onChange={(e) => onChange({ [key]: e.target.value || null })}
                        />
                        <InputError message={errors[key]} />
                    </div>
                ))}
            </fieldset>
            <InputError message={errors.filters} />
            <div className="flex gap-2">
                <Button type="submit" disabled={loading}>
                    <Search className="size-4 text-sky-300" />
                    Terapkan
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    aria-label="Reset filter"
                    title="Reset filter"
                    disabled={loading}
                    onClick={onReset}
                >
                    <RotateCcw className="size-4 text-amber-600 dark:text-amber-300" />
                </Button>
            </div>
        </form>
    );
}
