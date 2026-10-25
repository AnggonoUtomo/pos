import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeMenu } from '@/components/theme-menu';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-[var(--app-workspace)] p-6 md:p-10">
            <div className="absolute top-5 right-5">
                <ThemeMenu />
            </div>
            <div className="w-full max-w-[420px] rounded-lg border border-[var(--app-border)] bg-[var(--app-panel)] p-7 shadow-[var(--app-shadow)] sm:p-8">
                <div className="flex flex-col gap-7">
                    <div className="flex flex-col items-center gap-4">
                        <Link href={route('home')} className="mb-2 flex items-center gap-3 font-semibold">
                            <div className="bg-primary-solid text-primary-solid-foreground flex size-10 items-center justify-center rounded-lg">
                                <AppLogoIcon className="size-7 fill-current" />
                            </div>
                            <span className="text-lg">POS Modular</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-muted-foreground text-center text-sm">{description}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
