import { listMarkdownRoutes, readMarkdown, routeToUrl } from '../../../../src/lib/markdown'

// Markdown version of docs and news pages, for AI agents. Reached via:
// - /docs/<page>.md and /news/<page>.md (rewrites in next.config.mjs)
// - `Accept: text/markdown` on the HTML URL (rewrites in next.config.mjs)

// Generated at build time and served from the CDN, like the HTML pages.
// Other URLs are a 404, without running a function.
export const dynamic = 'force-static'
export const dynamicParams = false

export function generateStaticParams() {
    return listMarkdownRoutes().map(route => ({ slug: route.split('/') }))
}

export async function GET(req, { params }) {
    const { slug } = await params
    const route = Array.isArray(slug) ? slug.join('/') : (slug ?? '')

    const content = readMarkdown(route)
    if (content === null) {
        return new Response('Page not found', {
            status: 404,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        })
    }

    return new Response(content, {
        status: 200,
        headers: {
            'Content-Type': 'text/markdown; charset=utf-8',
            // The HTML page is the canonical version of this content
            Link: `<${routeToUrl(route)}>; rel="canonical"`,
            // The same URL serves HTML or Markdown depending on the Accept header
            Vary: 'Accept',
        },
    })
}
