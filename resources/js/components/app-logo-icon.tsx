import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/riho-logo.png"
            alt="Logo"
            {...props}
        />
    );
}
