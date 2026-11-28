import nextra from 'nextra'
import { withPlausibleProxy } from 'next-plausible'
import redirectsFile from './redirects.js'
import minLight from 'shiki/themes/min-light.mjs'

const withNextra = nextra({
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

// Entries with a `#` are hash-anchor redirects: the server never sees the
// hash, so they can't match here. They're handled client-side by
// src/components/HashRedirects.jsx instead.
const SITE_URL = 'https://bref.sh'
const LLMS_TXT_LINK = `<${SITE_URL}/llms.txt>; rel="llms-txt"`
const ACCEPTS_MARKDOWN = { type: 'header', key: 'accept', value: '.*text/markdown.*' }
// Regex lookahead for `source` patterns: excludes Next.js internals, API routes,
// `.md` URLs and static files (page routes can contain dots, e.g.
// /news/01-bref-1.0, so we cannot simply exclude every path that contains a dot)
const NOT_A_FILE = '(?!_next/|api/|_pagefind/|js/|.*\\.(?:md|txt|xml|json|png|jpe?g|gif|svg|ico|webp|woff2?|css|js|map)$)'

const redirectList = Object.entries(redirectsFile.redirects)
    .filter(([source]) => !source.includes('#'))
    .map(([source, destination]) => ({
        source,
        destination,
        permanent: true,
    }))

export default withNextra(withPlausibleProxy()({
    // Silence a Next.js warning: it detects lockfiles in other directories
    // (e.g. link-checker/) and can infer a wrong workspace root
    outputFileTracingRoot: import.meta.dirname,
    // Redirect old .html links + the entries from redirects.js
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
    // Advertise the machine-readable resources in HTTP headers, so that agents
    // that do not parse the HTML can still discover them.
    // (the last matching rule wins when several set the same header)
    async headers() {
        return [
            {
                source: `/:path(${NOT_A_FILE}.*)`,
                headers: [{ key: 'Link', value: LLMS_TXT_LINK }],
            },
            // Pages that have a Markdown version (see app/api/md and src/lib/markdown.js)
            {
                source: '/:root(docs|news)',
                headers: [{ key: 'Link', value: `${LLMS_TXT_LINK}, <${SITE_URL}/:root.md>; rel="alternate"; type="text/markdown"` }],
            },
            {
                source: `/:root(docs|news)/:path(${NOT_A_FILE}.+)`,
                headers: [{ key: 'Link', value: `${LLMS_TXT_LINK}, <${SITE_URL}/:root/:path.md>; rel="alternate"; type="text/markdown"` }],
            },
        ]
    },
    // Serve Markdown versions of docs and news pages for AI agents
    async rewrites() {
        return {
            // Content negotiation: serve Markdown to AI agents that ask for it.
            // Before the pages, so that it takes precedence over the HTML page.
            beforeFiles: [
                {
                    source: '/:root(docs|news)',
                    has: [ACCEPTS_MARKDOWN],
                    destination: '/api/md/:root',
                },
                {
                    source: `/:root(docs|news)/:path(${NOT_A_FILE}.+)`,
                    has: [ACCEPTS_MARKDOWN],
                    destination: '/api/md/:root/:path',
                },
            ],
            afterFiles: [
                {
                    source: '/docs/:path*.md',
                    destination: '/api/md/docs/:path*',
                },
                {
                    source: '/docs.md',
                    destination: '/api/md/docs',
                },
                {
                    source: '/news/:path*.md',
                    destination: '/api/md/news/:path*',
                },
            ],
        }
    },
}));
