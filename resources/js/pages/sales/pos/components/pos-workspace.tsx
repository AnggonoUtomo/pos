import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { LayoutGrid, ShoppingBasket } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import type { WorkspaceView } from '../lib/workspace';

export function PosWorkspace({
    view,
    locked,
    count,
    scanner,
    context,
    catalog,
    cart,
    summary,
}: {
    view: WorkspaceView;
    locked: boolean;
    count: number;
    scanner: ReactNode;
    context: ReactNode;
    catalog: ReactNode;
    cart: ReactNode;
    summary: ReactNode;
}) {
    const [pane, setPane] = useState('catalog');
    const visual = view === 'visual';
    useEffect(() => {
        const focusCatalog = (event: KeyboardEvent) => {
            if (event.altKey && event.key.toLowerCase() === 'f' && !document.querySelector('[role="dialog"]')) {
                setPane('catalog');
                requestAnimationFrame(() => document.getElementById('pos-search')?.focus());
            }
        };
        window.addEventListener('keydown', focusCatalog);
        return () => window.removeEventListener('keydown', focusCatalog);
    }, []);
    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <div
                className={cn(
                    'shrink-0 border-b border-[var(--app-border)] bg-[var(--app-panel)]',
                    visual && 'grid grid-cols-[minmax(0,1fr)] md:grid-cols-[minmax(12rem,0.8fr)_minmax(0,1.5fr)]',
                )}
            >
                <div className={cn('px-4 py-2', !visual && 'max-w-lg')}>{scanner}</div>
                <fieldset disabled={locked} className="min-w-0">
                    {context}
                </fieldset>
            </div>
            <ToggleGroup
                type="single"
                value={pane}
                onValueChange={(value) => value && setPane(value)}
                aria-label="Panel POS"
                className="shrink-0 justify-start border-b px-3 py-1 md:hidden"
            >
                <ToggleGroupItem value="catalog">
                    <LayoutGrid className="mr-2 size-4 text-teal-600 dark:text-teal-300" />
                    Barang
                </ToggleGroupItem>
                <ToggleGroupItem value="cart">
                    <ShoppingBasket className="mr-2 size-4 text-violet-600 dark:text-violet-300" />
                    Keranjang ({count})
                </ToggleGroupItem>
            </ToggleGroup>
            <fieldset
                disabled={locked}
                className={cn(
                    'grid min-h-0 min-w-0 flex-1 grid-cols-1 grid-rows-1',
                    visual ? 'md:grid-cols-[minmax(0,1.5fr)_minmax(21rem,1fr)]' : 'md:grid-cols-[minmax(0,1.1fr)_minmax(21rem,1fr)]',
                )}
            >
                <div className={cn('min-h-0 min-w-0 flex-col md:flex', pane === 'catalog' ? 'flex' : 'hidden')}>{catalog}</div>
                <div
                    className={cn(
                        'min-h-0 min-w-0 flex-col border-[var(--app-border)] bg-[var(--app-panel)] md:flex md:border-l',
                        pane === 'cart' ? 'flex' : 'hidden',
                    )}
                >
                    {cart}
                    {summary}
                </div>
            </fieldset>
        </div>
    );
}
