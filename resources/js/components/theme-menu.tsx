import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useAppearance, type AccentTheme, type Appearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Check, Monitor, Moon, Palette, Sun } from 'lucide-react';

const appearanceOptions: { value: Appearance; label: string; icon: typeof Sun }[] = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

const accentOptions: { value: AccentTheme; label: string; className: string }[] = [
    { value: 'default', label: 'Default', className: 'bg-neutral-950' },
    { value: 'clean', label: 'Clean Default', className: 'bg-slate-300 ring-1 ring-slate-500' },
    { value: 'grey', label: 'Grey', className: 'bg-slate-500' },
    { value: 'stone', label: 'Stone', className: 'bg-stone-600' },
    { value: 'forest', label: 'Forest', className: 'bg-emerald-600' },
    { value: 'ruby', label: 'Ruby', className: 'bg-rose-600' },
    { value: 'quartz', label: 'Quartz', className: 'bg-violet-600' },
    { value: 'aurora', label: 'Aurora', className: 'bg-cyan-600' },
    { value: 'copper', label: 'Copper', className: 'bg-orange-700' },
    { value: 'saffron', label: 'Saffron', className: 'bg-yellow-500' },
    { value: 'plum', label: 'Plum', className: 'bg-purple-700' },
];

export function ThemeMenu() {
    const { appearance, accentTheme, updateAppearance, updateAccentTheme } = useAppearance();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button type="button" variant="outline" size="sm" className="h-8 w-8 shrink-0 gap-0 px-0 xl:w-auto xl:gap-2 xl:px-3">
                    <Palette className="size-4 text-violet-600 dark:text-violet-300" />
                    <span className="hidden xl:inline">Tema</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel>Mode Tampilan</DropdownMenuLabel>
                {appearanceOptions.map(({ value, label, icon: Icon }) => (
                    <DropdownMenuItem key={value} onClick={() => updateAppearance(value)}>
                        <Icon className={cn('size-4', value === 'light' && 'text-amber-500', value === 'dark' && 'text-sky-500', value === 'system' && 'text-emerald-500')} />
                        <span>{label}</span>
                        {appearance === value && <Check className="ml-auto size-4" />}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Accent Theme</DropdownMenuLabel>
                <div className="grid grid-cols-2 gap-1 p-1">
                    {accentOptions.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => updateAccentTheme(option.value)}
                            className={cn(
                                'hover:bg-accent focus:bg-accent flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden transition-colors',
                                accentTheme === option.value && 'bg-accent text-accent-foreground',
                            )}
                        >
                            <span className={cn('size-3 rounded-full ring-2 ring-background', option.className)} />
                            <span>{option.label}</span>
                            {accentTheme === option.value && <Check className="ml-auto size-3.5" />}
                        </button>
                    ))}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
