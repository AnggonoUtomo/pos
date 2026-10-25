import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="bg-primary text-primary-foreground flex aspect-square size-9 shrink-0 items-center justify-center rounded-lg">
                <AppLogoIcon className="size-6 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate text-base leading-none font-semibold">POS Modular</span>
            </div>
        </>
    );
}
