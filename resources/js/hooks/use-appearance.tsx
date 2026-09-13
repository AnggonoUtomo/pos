import { useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';
export type AccentTheme = 'default' | 'grey' | 'stone' | 'forest' | 'ruby' | 'quartz' | 'aurora' | 'copper' | 'saffron' | 'plum';

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = (appearance: Appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
};

const applyAccentTheme = (theme: AccentTheme) => {
    document.documentElement.dataset.theme = theme;
};

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => {
    const currentAppearance = localStorage.getItem('appearance') as Appearance;
    applyTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    const savedAppearance = (localStorage.getItem('appearance') as Appearance) || 'system';
    const savedAccentTheme = (localStorage.getItem('accent-theme') as AccentTheme) || 'default';

    applyTheme(savedAppearance);
    applyAccentTheme(savedAccentTheme);

    // Add the event listener for system theme changes...
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('system');
    const [accentTheme, setAccentTheme] = useState<AccentTheme>('default');

    const updateAppearance = (mode: Appearance) => {
        setAppearance(mode);
        localStorage.setItem('appearance', mode);
        applyTheme(mode);
    };

    const updateAccentTheme = (theme: AccentTheme) => {
        setAccentTheme(theme);
        localStorage.setItem('accent-theme', theme);
        applyAccentTheme(theme);
    };

    useEffect(() => {
        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
        const savedAccentTheme = localStorage.getItem('accent-theme') as AccentTheme | null;

        updateAppearance(savedAppearance || 'system');
        updateAccentTheme(savedAccentTheme || 'default');

        return () => mediaQuery.removeEventListener('change', handleSystemThemeChange);
    }, []);

    return { appearance, accentTheme, updateAppearance, updateAccentTheme };
}
