/* eslint-env node */
import { Layout, Navbar } from 'nextra-theme-docs'
import { Banner, Head } from 'nextra/components'
import { getPageMap } from 'nextra/page-map'
import { Inter } from 'next/font/google'
import PlausibleProvider from 'next-plausible'
import { AnimatedLogo } from '../src/components/AnimatedLogo'
import Footer from '../src/components/Footer'
import HashRedirects from '../src/components/HashRedirects'
import 'nextra-theme-docs/style.css'
import '../styles/globals.css'

const inter = Inter({ subsets: ['latin'], variable: '--font-inter' })

// Root metadata replicates useNextSeoProps title template + default OG (was theme.config head/useNextSeoProps).
export const metadata = {
    metadataBase: new URL('https://bref.sh'),
    title: {
        template: '%s – Bref',
        default: 'Bref – Simple and scalable PHP with serverless',
    },
    description: 'Bref is a framework to write and deploy serverless PHP applications on AWS Lambda.',
    openGraph: {
        siteName: 'Bref',
        locale: 'en_US',
        images: [{ url: 'https://bref.sh/social-card.png' }],
    },
    twitter: { card: 'summary_large_image', creator: '@brefphp' },
    other: { 'google-site-verification': 'RRmKDrWI2l69B0nMwv4ndrYOHSuaTBfarvCgtJxMpXA' },
}

const banner = (
    <Banner storageKey="v3">
        <a href="/news/03-bref-3.0">🎉 Bref 3.0 is released. Read more →</a>
    </Banner>
)

// Slack icon (was theme.config chat.icon)
const slackIcon = (
    <svg width="24" height="24" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg">
        <title>Slack</title>
        <path
            fill="currentColor"
            d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"
        />
    </svg>
)

const navbar = (
    <Navbar
        logo={<AnimatedLogo className="h-8" />}
        projectLink="https://github.com/brefphp/bref"
        chatLink="https://bref.sh/slack"
        chatIcon={slackIcon}
    />
)

export default async function RootLayout({ children }) {
    const pageMap = await getPageMap()
    return (
        <html lang="en" dir="ltr" className={inter.variable} suppressHydrationWarning>
            <Head>
                <link rel="icon" type="image/x-icon" href="/favicon.ico" />
                <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16" />
                <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32" />
                <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/apple-touch-icon-144x144.png" />
                <link rel="apple-touch-icon-precomposed" sizes="152x152" href="/apple-touch-icon-152x152.png" />
            </Head>
            <body>
                <PlausibleProvider domain="bref.sh" trackOutboundLinks={true}>
                    <HashRedirects />
                    <Layout
                        banner={banner}
                        navbar={navbar}
                        footer={<Footer />}
                        pageMap={pageMap}
                        docsRepositoryBase="https://github.com/brefphp/bref/blob/main"
                        sidebar={{ defaultMenuCollapseLevel: 1 }}
                        darkMode={false}
                        nextThemes={{ defaultTheme: 'light', forcedTheme: 'light' }}
                    >
                        {children}
                    </Layout>
                </PlausibleProvider>
            </body>
        </html>
    )
}
