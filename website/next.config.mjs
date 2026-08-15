import nextra from 'nextra'
import { withPlausibleProxy } from 'next-plausible'
import redirectsFile from './redirects.js'

const withNextra = nextra({
    theme: 'nextra-theme-docs',
    themeConfig: './theme.config.jsx',
    // Show the copy button on all code blocks
    // https://nextra.site/docs/guide/syntax-highlighting#copy-button
    defaultShowCopyCode: true,
})

export default withNextra(withPlausibleProxy()({
    // Redirect old .html links
    async redirects() {
        const redirectList = Object.entries(redirectsFile.redirects)
            .map(([source, destination]) => ({
                source,
                destination,
                permanent: true,
            }));
        return [
            {
                source: '/docs/:path*.html',
                destination: '/docs/:path*',
                permanent: true,
            },
            ...redirectList,
        ]
    },
    // Serve Markdown versions of docs for AI crawlers
    async rewrites() {
        return [
            {
                source: '/docs/:path*.md',
                destination: '/api/md/:path*',
            },
        ]
    },
}));
