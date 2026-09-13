import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="POS Modular">
            <path d="M8 4h24a4 4 0 0 1 4 4v28l-4-2-4 2-4-2-4 2-4-2-4 2-4-2-4 2V8a4 4 0 0 1 4-4Z" />
            <path d="M12 11h16v3H12v-3Zm0 7h16v2H12v-2Zm0 6h8v2h-8v-2Z" fill="currentColor" opacity=".35" />
        </svg>
    );
}
