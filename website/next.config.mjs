import nextra from 'nextra'
import { withPlausibleProxy } from 'next-plausible'
import redirectsFile from './redirects.js'
import minLight from 'shiki/themes/min-light.mjs'

const withNextra = nextra({
    theme: 'nextra-theme-docs',
    themeConfig: './theme.config.jsx',
    // Show the copy button on all code blocks
    // https://nextra.site/docs/guide/syntax-highlighting#copy-button
    defaultShowCopyCode: true,
    mdxOptions: {
        rehypePrettyCodeOptions: {
            // Syntax highlighting theme, pick from https://shiki.style/themes
            theme: {
                light: {
                    ...minLight,
                    // min-light doesn't color `diff` code blocks: add colors
                    // for these, reusing the min-light color palette
                    tokenColors: [
                        ...minLight.tokenColors,
                        {
                            scope: ['markup.deleted', 'punctuation.definition.deleted'],
                            settings: { foreground: '#D32F2F' },
                        },
                        {
                            scope: ['markup.inserted', 'punctuation.definition.inserted'],
                            settings: { foreground: '#22863A' },
                        },
                        {
                            scope: ['meta.diff.range', 'meta.diff.header'],
                            settings: { foreground: '#1976D2' },
                        },
                    ],
                },
                dark: 'min-dark',
            },
        },
    },
})

export default withNextra(withPlausibleProxy()({
    // Silence a Next.js warning: it detects lockfiles in other directories
    // (e.g. link-checker/) and can infer a wrong workspace root
    outputFileTracingRoot: import.meta.dirname,
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
