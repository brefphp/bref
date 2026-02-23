import { NextResponse } from 'next/server';

export function middleware(request) {
    const accept = request.headers.get('accept') || '';

    // Content negotiation: serve Markdown for AI crawlers requesting it
    if (accept.includes('text/markdown') && request.nextUrl.pathname.startsWith('/docs/')) {
        const mdPath = request.nextUrl.pathname.replace('/docs/', '/api/md/');
        return NextResponse.rewrite(new URL(mdPath, request.url));
    }

    return NextResponse.next();
}

export const config = {
    matcher: '/docs/:path*',
};
