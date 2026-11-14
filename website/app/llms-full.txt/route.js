import { getDocsSections } from '../../src/lib/site-index'
import { readMarkdown, SITE_URL } from '../../src/lib/markdown'

// The whole documentation as a single Markdown file, for AI agents.
export const dynamic = 'force-static'

export async function GET() {
    const sections = await getDocsSections()

    const parts = [
        '# Bref documentation',
        '',
        `> Bref is an open-source framework to run PHP applications on AWS Lambda (serverless). This file contains the whole documentation of ${SITE_URL}. Each page starts with its canonical URL.`,
        '',
    ]
    for (const section of sections) {
        for (const page of section.pages) {
            const content = readMarkdown(page.route)
            if (content === null) continue
            parts.push('---', '', `Source: ${SITE_URL}${page.route}`, '', content.trim(), '')
        }
    }

    return new Response(parts.join('\n'), {
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    })
}
