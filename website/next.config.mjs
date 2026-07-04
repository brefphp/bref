import nextra from 'nextra'
import { withPlausibleProxy } from 'next-plausible'
import redirectsPkg from './redirects.js'

const { redirects } = redirectsPkg

const withNextra = nextra({
    // Show the copy button on all code blocks
    // https://nextra.site/docs/guide/syntax-highlighting#copy-button
    defaultShowCopyCode: true,
})

// Entries with a `#` are hash-anchor redirects: the server never sees the
// hash, so they can't match here. They're handled client-side by
// src/components/HashRedirects.jsx instead.
const redirectList = Object.entries(redirects)
    .filter(([source]) => !source.includes('#'))
    .map(([source, destination]) => ({
        source,
        destination,
        permanent: true,
    }))

export default withNextra(withPlausibleProxy()({
    outputFileTracingRoot: import.meta.dirname,
    // Redirect old .html links + the entries from redirects.js (router-agnostic)
    async redirects() {
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
            {
                source: '/docs.md',
                destination: '/api/md',
            },
        ]
    },
}))
