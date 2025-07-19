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
        // On SSG the path is `/index` instead of `/` for some reason
        if (asPath === '/' || asPath === '/index') {
            return {
                titleTemplate: 'Bref – Simple and scalable PHP with serverless',
                openGraph: {
                    images: [
                        {
                            url: 'https://bref.sh/social-card.png'
                        }
                    ]
                }
            };
        } else {
            return {
                titleTemplate: '%s – Bref',
                openGraph: {
                    images: [
                        {
                            url: 'https://bref.sh/social-card.png'
                        }
                    ]
                }
            };
        }
    },
    chat: {
        link: 'https://bref.sh/slack',
        icon: (
            <svg width="24" height="24" viewBox="0 0 24 24" role="img"
                 xmlns="http://www.w3.org/2000/svg"><title>Slack</title>
                <path
                    fill="currentColor"
                    d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z" />
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
            <meta name="twitter:card" content="summary_large_image" />
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
