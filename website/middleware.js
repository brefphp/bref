import { NextResponse } from 'next/server';

const SITE_URL = 'https://bref.sh';

// Routes that have a Markdown version (see app/api/md and src/lib/markdown.js)
const MARKDOWN_ROUTES = /^\/(docs|news)(\/|$)/;

export function middleware(request) {
    const { pathname } = request.nextUrl;
    // `.md` URLs are already served as Markdown (rewrites in next.config.mjs)
    if (pathname.endsWith('.md')) return NextResponse.next();
    const hasMarkdown = MARKDOWN_ROUTES.test(pathname);

    // Content negotiation: serve Markdown to AI agents that ask for it
    const accept = request.headers.get('accept') || '';
    if (hasMarkdown && accept.includes('text/markdown')) {
        return NextResponse.rewrite(new URL('/api/md' + pathname, request.url));
    }

    // Advertise the machine-readable resources in HTTP headers, so that agents
    // that do not parse the HTML can still discover them.
    const links = [`<${SITE_URL}/llms.txt>; rel="llms-txt"`];
    if (hasMarkdown) {
        links.push(`<${SITE_URL}${pathname.replace(/\/$/, '')}.md>; rel="alternate"; type="text/markdown"`);
    }
    const response = NextResponse.next();
    response.headers.set('Link', links.join(', '));
    return response;
}

export const config = {
    // All pages, excluding Next.js internals, API routes and static files.
    // (page routes can contain dots, e.g. /news/01-bref-1.0, so we cannot
    // simply exclude every path that contains a dot)
    matcher: ['/((?!_next/|api/|_pagefind/|js/|.*\\.(?:txt|xml|json|png|jpe?g|gif|svg|ico|webp|woff2?|css|js|map)$).*)'],
};
