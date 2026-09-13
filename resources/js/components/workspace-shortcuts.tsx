import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { ChevronDown, Keyboard } from 'lucide-react';

export interface WorkspaceShortcut {
    label: string;
    description: string;
    keys: string[];
    disabled?: boolean;
}

function ShortcutKeys({ keys }: { keys: string[] }) {
    return (
        <span className="ml-auto flex shrink-0 items-center gap-1">
            {keys.map((key) => (
                <kbd key={key} className="bg-muted text-muted-foreground rounded border px-1.5 py-0.5 text-[11px] leading-none font-medium">
                    {key}
                </kbd>
            ))}
        </span>
    );
}

export function WorkspaceShortcutDropdown({
    title = 'Shortcut Operasi',
    description = 'Aksi cepat halaman ini',
    shortcuts,
    className,
}: {
    title?: string;
    description?: string;
    shortcuts: WorkspaceShortcut[];
    className?: string;
}) {
    return (
        <div className={cn('flex items-center justify-between gap-3 border-b bg-muted/30 px-4 py-2 lg:px-6', className)}>
            <div className="min-w-0">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-muted-foreground truncate text-xs">{description}</p>
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" className="h-8 shrink-0 gap-2">
                        <Keyboard className="size-4" />
                        Shortcut
                        <ChevronDown className="size-3.5" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-80">
                    <DropdownMenuLabel>Shortcut Halaman</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {shortcuts.map((shortcut) => (
                        <DropdownMenuItem key={`${shortcut.label}-${shortcut.keys.join('-')}`} disabled={shortcut.disabled} className="items-start gap-3 py-2">
                            <div className="min-w-0">
                                <p className="text-sm font-medium">{shortcut.label}</p>
                                <p className="text-muted-foreground text-xs">{shortcut.description}</p>
                            </div>
                            <ShortcutKeys keys={shortcut.keys} />
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
