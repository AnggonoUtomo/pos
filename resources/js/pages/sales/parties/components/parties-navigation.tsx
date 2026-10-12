import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Building2, Layers } from 'lucide-react';

export function PartiesNavigation({ levels = false }: { levels?: boolean }) {
    return (
        <nav aria-label="Data pelanggan" className="flex flex-wrap gap-4 border-b pb-3">
            {[
                { title: 'Pelanggan', name: 'sales.customers.index', active: !levels, icon: Building2, tone: 'text-cyan-600 dark:text-cyan-300' },
                {
                    title: 'Level pelanggan',
                    name: 'sales.customer-levels.index',
                    active: levels,
                    icon: Layers,
                    tone: 'text-violet-600 dark:text-violet-300',
                },
            ].map((item) => (
                <Link
                    key={item.name}
                    href={route(item.name)}
                    aria-current={item.active ? 'page' : undefined}
                    className={cn(
                        'flex items-center gap-2 rounded-sm px-2 py-1 text-sm',
                        item.active ? 'bg-muted font-semibold' : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <item.icon className={cn('size-4', item.tone)} />
                    {item.title}
                </Link>
            ))}
        </nav>
    );
}
