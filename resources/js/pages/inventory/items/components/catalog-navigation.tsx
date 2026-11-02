import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Package, Shapes, Tag } from 'lucide-react';

export function CatalogNavigation({ active }: { active: 'items' | 'categories' | 'brands' }) {
    return (
        <nav className="flex flex-wrap gap-2" aria-label="Master barang">
            {(
                [
                    { key: 'items', title: 'Barang', icon: Package, color: 'text-emerald-600 dark:text-emerald-300' },
                    { key: 'categories', title: 'Kategori', icon: Shapes, color: 'text-sky-600 dark:text-sky-300' },
                    { key: 'brands', title: 'Merek', icon: Tag, color: 'text-amber-600 dark:text-amber-300' },
                ] as const
            ).map(({ key, title, icon: Icon, color }) => (
                <Button key={key} asChild variant={active === key ? 'secondary' : 'outline'} size="sm">
                    <Link href={route(`inventory.${key}.index`)} aria-current={active === key ? 'page' : undefined}>
                        <Icon className={color} />
                        {title}
                    </Link>
                </Button>
            ))}
        </nav>
    );
}
