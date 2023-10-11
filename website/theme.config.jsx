// https://nextra.site/docs/docs-theme/theme-configuration
import { useRouter } from 'next/router';
import { AnimatedLogo } from './src/components/AnimatedLogo';
import Footer from './src/components/Footer';
import { DocSearch } from '@docsearch/react';

export default {
    logo: <AnimatedLogo className="h-8" />,
    docsRepositoryBase: 'https://github.com/brefphp/bref/blob/main',
    project: {
        link: 'https://github.com/brefphp/bref'
    },
    useNextSeoProps() {
        const { asPath } = useRouter();
        if (asPath.length > 1) {
            return {
                titleTemplate: '%s – Bref',
            };
        } else {
            return {
                titleTemplate: 'Bref – Simple and scalable PHP with serverless',
            };
        }
    },
    chat: {
        link: 'https://twitter.com/brefphp',
        icon: (
            <svg width="24" height="24" viewBox="0 0 248 204">
                <path
                    fill="currentColor"
                    d="M221.95 51.29c.15 2.17.15 4.34.15 6.53 0 66.73-50.8 143.69-143.69 143.69v-.04c-27.44.04-54.31-7.82-77.41-22.64 3.99.48 8 .72 12.02.73 22.74.02 44.83-7.61 62.72-21.66-21.61-.41-40.56-14.5-47.18-35.07a50.338 50.338 0 0 0 22.8-.87C27.8 117.2 10.85 96.5 10.85 72.46v-.64a50.18 50.18 0 0 0 22.92 6.32C11.58 63.31 4.74 33.79 18.14 10.71a143.333 143.333 0 0 0 104.08 52.76 50.532 50.532 0 0 1 14.61-48.25c20.34-19.12 52.33-18.14 71.45 2.19 11.31-2.23 22.15-6.38 32.07-12.26a50.69 50.69 0 0 1-22.2 27.93c10.01-1.18 19.79-3.86 29-7.95a102.594 102.594 0 0 1-25.2 26.16z"
                />
            </svg>
        )
    },
    darkMode: false,
    nextThemes: {
        defaultTheme: 'light',
        forcedTheme: 'light',
    },
    primaryHue: 202,
    sidebar: {
        defaultMenuCollapseLevel: 1,
    },
    head: (
        <>
            <link rel="icon" type="image/x-icon" href="/favicon.ico" />
            <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16" />
            <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32" />
            <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/apple-touch-icon-144x144.png" />
            <link rel="apple-touch-icon-precomposed" sizes="152x152" href="/apple-touch-icon-152x152.png" />
            <meta property="og:locale" content="en_US" />
            <meta property="og:site_name" content="Bref" />
            <meta name="twitter:creator" content="@brefphp" />
            <meta name="google-site-verification" content="RRmKDrWI2l69B0nMwv4ndrYOHSuaTBfarvCgtJxMpXA" />
        </>
    ),
    footer: {
        component: Footer,
        text: (
            <span>
                MIT {new Date().getFullYear()} ©{' '}
                <a href="https://mnapoli.fr">
                    Matthieu Napoli
                </a>
                .
            </span>
        )
    },
    search: {
        component: <DocSearch appId="7J23TEKSTT" indexName="bref" apiKey="0d252e6edd70998021bc0044444c42c4" />
    },
    components: {
        // https://github.com/shuding/nextra/blob/main/packages/nextra-theme-docs/src/mdx-components.tsx
        h1: props => (
            <h1
                className="mt-2 text-4xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                {...props}
            />
        ),
    }
}
