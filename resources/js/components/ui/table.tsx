import { cn } from '@/lib/utils';
import type { ComponentProps } from 'react';

export function Table({ className, ...props }: ComponentProps<'table'>) {
    return <table className={cn('w-full caption-bottom text-sm', className)} {...props} />;
}
export function TableHeader({ className, ...props }: ComponentProps<'thead'>) {
    return <thead className={cn('bg-muted/40 [&_tr]:border-b', className)} {...props} />;
}
export function TableBody({ className, ...props }: ComponentProps<'tbody'>) {
    return <tbody className={cn('[&_tr:last-child]:border-0', className)} {...props} />;
}
export function TableRow({ className, ...props }: ComponentProps<'tr'>) {
    return <tr className={cn('border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted', className)} {...props} />;
}
export function TableHead({ className, ...props }: ComponentProps<'th'>) {
    return <th className={cn('h-12 px-3 text-left align-middle font-medium whitespace-nowrap', className)} {...props} />;
}
export function TableCell({ className, ...props }: ComponentProps<'td'>) {
    return <td className={cn('p-3 align-middle', className)} {...props} />;
}
