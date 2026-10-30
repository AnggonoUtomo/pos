import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RotateCcw, Search } from 'lucide-react';
import type { RefObject } from 'react';
import type { AuditFilters, AuditProps } from '../types';

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
    draft: AuditFilters;
    options: AuditProps['options'];
    onChange: (patch: Partial<AuditFilters>) => void;
    onApply: () => void;
    onReset: () => void;
    loading: boolean;
    errors: Record<string, string>;
    searchRef: RefObject<HTMLInputElement | null>;
}) {
    return (
        <form
            className="workspace-filters"
            onSubmit={(event) => {
                event.preventDefault();
                onApply();
            }}
        >
            <fieldset disabled={loading} className="grid min-w-0 items-end gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-12">
                <legend className="sr-only">Filter audit log</legend>
                <div className="min-w-0 space-y-2 sm:col-span-2 lg:col-span-3 xl:col-span-6">
                    <Label htmlFor="audit-search">Pencarian</Label>
                    <div className="relative">
                        <Search aria-hidden="true" className="pointer-events-none absolute top-3 left-3 size-4 text-sky-600 dark:text-sky-300" />
                        <Input
                            id="audit-search"
                            ref={searchRef}
                            className="pl-9"
                            value={draft.search}
                            maxLength={150}
                            placeholder="Kode/nomor, ID objek, atau pengguna..."
                            onChange={(event) => onChange({ search: event.target.value })}
                        />
                    </div>
                    <InputError message={errors.search} />
                </div>
                {(
                    [
                        { key: 'module', label: 'Modul', choices: options.modules },
                        { key: 'actor_id', label: 'Pengguna', choices: [{ id: 'system', name: 'Sistem' }, ...options.actors] },
                        { key: 'event', label: 'Tindakan', choices: options.events },
                    ] as const
                ).map(({ key, label, choices }) => (
                    <div key={key} className="min-w-0 space-y-2 xl:col-span-3">
                        <Label htmlFor={`audit-${key}`}>{label}</Label>
                        <Select
                            value={draft[key] ?? 'all'}
                            disabled={loading}
                            onValueChange={(value) => onChange({ [key]: value === 'all' ? null : value })}
                        >
                            <SelectTrigger id={`audit-${key}`} className="w-full min-w-0 [&>span]:truncate">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua {label.toLowerCase()}</SelectItem>
                                {draft[key] && !choices.some((choice) => choice.id === draft[key]) && (
                                    <SelectItem value={draft[key]!}>Pilihan tidak tersedia</SelectItem>
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
                ))}
                {(['date_from', 'date_to'] as const).map((key) => (
                    <div key={key} className="min-w-0 space-y-2 xl:col-span-3">
                        <Label htmlFor={`audit-${key}`}>{key === 'date_from' ? 'Dari tanggal (WIB)' : 'Sampai tanggal (WIB)'}</Label>
                        <Input
                            id={`audit-${key}`}
                            type="date"
                            min="1900-01-01"
                            max="9998-12-31"
                            value={draft[key] ?? ''}
                            onChange={(event) => onChange({ [key]: event.target.value || null })}
                        />
                        <InputError message={errors[key]} />
                    </div>
                ))}
                <div className="flex gap-2 xl:col-span-3">
                    <Button type="submit" disabled={loading}>
                        <Search className="text-sky-200" />
                        Terapkan
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        title="Reset filter"
                        aria-label="Reset filter"
                        disabled={loading}
                        onClick={onReset}
                    >
                        <RotateCcw className="text-amber-600 dark:text-amber-300" />
                    </Button>
                </div>
            </fieldset>
            <InputError message={errors.filters} />
        </form>
    );
}
