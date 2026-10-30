import { Input } from '@/components/ui/input';
import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import type { CustomerOption } from '../types';

export function CustomerAutocomplete({
    customers,
    customer,
    disabled,
    onChange,
}: {
    customers: CustomerOption[];
    customer?: CustomerOption;
    disabled: boolean;
    onChange: (id: string) => void;
}) {
    const [query, setQuery] = useState('');
    const search = query.trim().toLocaleLowerCase('id-ID');
    const filtered = customers.filter((row) => `${row.name} ${row.code} ${row.levelName}`.toLocaleLowerCase('id-ID').includes(search));
    return (
        <Combobox
            value={customer ?? null}
            by="id"
            immediate
            disabled={disabled}
            onChange={(next) => {
                if (next) onChange(next.id);
            }}
            onClose={() => setQuery('')}
        >
            <div className="relative min-w-0">
                <ComboboxInput
                    as={Input}
                    id="pos-customer"
                    className="workspace-search-field pr-10"
                    autoComplete="off"
                    maxLength={150}
                    placeholder="Cari nama atau kode pelanggan"
                    displayValue={(value: CustomerOption | null) => value?.name ?? ''}
                    onChange={(event) => setQuery(event.target.value)}
                />
                <ComboboxButton
                    className="text-muted-foreground absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-md disabled:cursor-not-allowed disabled:opacity-50"
                    aria-label="Daftar pelanggan"
                    title="Daftar pelanggan"
                >
                    <ChevronsUpDown className="size-4 text-sky-600 dark:text-sky-300" />
                </ComboboxButton>
            </div>
            <ComboboxOptions
                anchor="bottom start"
                modal={false}
                className="bg-popover text-popover-foreground z-50 max-h-72 w-[var(--input-width)] max-w-[calc(100vw-2rem)] min-w-64 overflow-y-auto overscroll-contain rounded-md border p-1 shadow-md [--anchor-gap:4px]"
            >
                {filtered.length === 0 ? (
                    <p role="status" className="text-muted-foreground px-3 py-4 text-sm">
                        Pelanggan tidak ditemukan.
                    </p>
                ) : (
                    filtered.map((row) => (
                        <ComboboxOption
                            key={row.id}
                            value={row}
                            aria-label={row.name}
                            aria-description={`${row.code}, ${row.levelName}`}
                            className="group data-focus:bg-accent data-focus:text-accent-foreground flex cursor-pointer items-center gap-2 rounded-sm px-3 py-2 select-none"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium break-words">{row.name}</p>
                                <p className="text-muted-foreground text-xs break-words">
                                    {row.code} · {row.levelName}
                                </p>
                            </div>
                            <Check className="invisible size-4 shrink-0 text-emerald-600 group-data-selected:visible dark:text-emerald-300" />
                        </ComboboxOption>
                    ))
                )}
            </ComboboxOptions>
        </Combobox>
    );
}
