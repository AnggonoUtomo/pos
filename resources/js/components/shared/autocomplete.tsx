import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions, Portal } from '@headlessui/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState, type InputHTMLAttributes } from 'react';
import { filterOptions, flattenOptions, optionText, type AutocompleteEntry, type AutocompleteOption } from './autocomplete-options';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'defaultValue' | 'onChange' | 'size'> & {
    value?: string;
    onValueChange: (value: string) => void;
    options: AutocompleteEntry[];
    onSearchChange?: (query: string) => void;
};

export function Autocomplete({
    value = '',
    onValueChange,
    options,
    onSearchChange,
    disabled,
    className,
    placeholder = 'Cari atau pilih...',
    ...props
}: Props) {
    const [query, setQuery] = useState('');
    const input = useRef<HTMLInputElement>(null);
    const toggle = useRef<HTMLButtonElement>(null);
    const scope = useRef<HTMLElement | null>(null);
    useLayoutEffect(() => {
        scope.current = input.current?.closest<HTMLElement>('[role="dialog"]') ?? document.body;
    }, []);
    useEffect(() => {
        // Radix handles Escape at document capture, before Headless UI's input.
        const escape = (event: KeyboardEvent) => {
            if (event.key !== 'Escape' || event.isComposing || input.current?.getAttribute('aria-expanded') !== 'true') return;
            if (event.target !== input.current && event.target !== toggle.current) return;
            event.preventDefault();
            event.stopPropagation();
            toggle.current?.click();
        };
        window.addEventListener('keydown', escape, true);
        return () => window.removeEventListener('keydown', escape, true);
    }, []);
    const choices = flattenOptions(options);
    const selected = choices.find((row) => row.value === value) ?? null;
    const filtered = filterOptions(choices, query);
    return (
        <Portal.Group target={scope}>
            <Combobox
                value={selected}
                by="value"
                immediate
                disabled={disabled}
                onChange={(next: AutocompleteOption | null) => {
                    if (next && !next.disabled) onValueChange(next.value);
                }}
                onClose={() => {
                    setQuery('');
                    onSearchChange?.('');
                }}
            >
                <div className={cn('relative min-w-0', className)}>
                    <ComboboxInput
                        as={Input}
                        ref={input}
                        {...props}
                        disabled={disabled}
                        className="workspace-search-field w-full pr-9"
                        autoComplete="off"
                        maxLength={150}
                        placeholder={placeholder}
                        displayValue={(option: AutocompleteOption | null) => (option ? optionText(option.label) : '')}
                        onChange={(e) => {
                            setQuery(e.target.value);
                            onSearchChange?.(e.target.value);
                        }}
                    />
                    <ComboboxButton
                        ref={toggle}
                        type="button"
                        aria-label="Tampilkan pilihan"
                        title="Tampilkan pilihan"
                        className="absolute inset-y-0 right-0 flex w-9 items-center justify-center rounded-r-md disabled:opacity-50"
                    >
                        <ChevronsUpDown className="size-4 text-sky-600 dark:text-sky-300" />
                    </ComboboxButton>
                </div>
                <ComboboxOptions
                    anchor="bottom start"
                    modal={false}
                    className="bg-popover text-popover-foreground pointer-events-auto z-[80] max-h-64 w-[var(--input-width)] max-w-[calc(100vw-2rem)] overflow-y-auto overscroll-contain rounded-md border p-1 shadow-md [--anchor-gap:4px]"
                >
                    {filtered.length === 0 && (
                        <p role="status" className="text-muted-foreground px-3 py-3 text-sm">
                            Tidak ada pilihan yang cocok.
                        </p>
                    )}
                    {filtered.slice(0, 100).map((row) => (
                        <ComboboxOption
                            key={row.value}
                            value={row}
                            disabled={row.disabled}
                            aria-label={optionText(row.label)}
                            className="group data-focus:bg-accent data-focus:text-accent-foreground flex cursor-pointer items-center gap-2 rounded-sm px-3 py-2 text-sm select-none data-disabled:cursor-not-allowed data-disabled:opacity-50"
                        >
                            <span className="min-w-0 flex-1 break-words">{row.label}</span>
                            <Check className="invisible size-4 shrink-0 text-emerald-600 group-data-selected:visible dark:text-emerald-300" />
                        </ComboboxOption>
                    ))}
                    {filtered.length > 100 && (
                        <p role="status" className="text-muted-foreground px-3 py-2 text-xs">
                            100 dari {filtered.length.toLocaleString('id-ID')} pilihan. Persempit pencarian.
                        </p>
                    )}
                </ComboboxOptions>
            </Combobox>
        </Portal.Group>
    );
}
