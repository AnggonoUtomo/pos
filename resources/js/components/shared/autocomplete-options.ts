import { Children, isValidElement, type ReactNode } from 'react';

export type AutocompleteOption = { value: string; label: ReactNode; disabled?: boolean; searchText?: string };
export type AutocompleteEntry = AutocompleteOption | AutocompleteEntry[] | false | null | undefined | '' | 0;

export function optionText(label: ReactNode): string {
    return Children.toArray(label)
        .map((node): string => {
            if (typeof node === 'string' || typeof node === 'number' || typeof node === 'bigint') return String(node);
            return isValidElement<{ children?: ReactNode }>(node) ? optionText(node.props.children) : '';
        })
        .join('')
        .replace(/\s+/g, ' ')
        .trim();
}

export function flattenOptions(entries: AutocompleteEntry[]): AutocompleteOption[] {
    const result = new Map<string, AutocompleteOption>();
    const add = (entry: AutocompleteEntry): void => {
        if (Array.isArray(entry)) entry.forEach(add);
        else if (entry) result.set(entry.value, entry);
    };
    entries.forEach(add);
    return [...result.values()];
}

export function filterOptions(options: AutocompleteOption[], query: string): AutocompleteOption[] {
    const search = query.trim().toLocaleLowerCase('id-ID');
    return options.filter((option) => `${optionText(option.label)} ${option.searchText ?? ''}`.toLocaleLowerCase('id-ID').includes(search));
}
