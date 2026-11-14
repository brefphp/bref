import { getDocsSections, getNewsPages } from '../../src/lib/site-index'
import { SITE_URL } from '../../src/lib/markdown'

// https://llmstxt.org/ - an index of the website for AI agents.
export const dynamic = 'force-static'

const intro = `# Bref

> Bref is an open-source framework to run PHP applications on AWS Lambda (serverless): PHP runtimes for Lambda, deployment tooling, and integrations for Laravel and Symfony. Bref Cloud is the hosted service that deploys, monitors and operates Bref applications in your own AWS account.

Every documentation page is available as Markdown: append \`.md\` to its URL (for example ${SITE_URL}/docs/laravel/getting-started.md), or request the HTML URL with an \`Accept: text/markdown\` header. The whole documentation is also available as a single file: ${SITE_URL}/llms-full.txt

Bref is free and open-source (MIT license). Bref Cloud has a free plan for personal projects and paid plans with a free trial: ${SITE_URL}/cloud#pricing
`

const line = page => `- [${page.title}](${SITE_URL}${page.route}${page.md ? '.md' : ''})${page.description ? `: ${page.description}` : ''}`

export async function GET() {
    const sections = await getDocsSections()
    const news = await getNewsPages()

    const docs = sections.map(section => [
        `## ${section.title ? `Documentation: ${section.title}` : 'Documentation'}`,
        '',
        ...section.pages.map(page => line({ ...page, md: true })),
        '',
    ].join('\n'))

    const site = [
        '## Bref Cloud',
        '',
        line({ title: 'Bref Cloud', route: '/cloud', description: 'Serverless PHP hosting on AWS Lambda: deploy, monitor and operate PHP applications in your own AWS account. Features, how it works, plans and pricing.' }),
        line({ title: 'Bref Cloud documentation', route: '/docs/cloud', md: true, description: 'What Bref Cloud does and how to get started.' }),
        '',
        '## Support and community',
        '',
        line({ title: 'Support plans', route: '/support', description: 'Consulting and support plans for serverless migrations to AWS with Bref and PHP.' }),
        line({ title: 'Community', route: '/docs/community', md: true, description: 'Slack, GitHub and other places to get help.' }),
        '- [GitHub repository](https://github.com/brefphp/bref): source code, issues and releases of the open-source project.',
        '',
        '## News',
        '',
        ...news.map(page => line({ ...page, md: true })),
        '',
        '## Optional',
        '',
        `- [Full documentation in one file](${SITE_URL}/llms-full.txt)`,
        `- [Sitemap](${SITE_URL}/sitemap.xml)`,
        '',
    ].join('\n')

    return new Response([intro, ...docs, site].join('\n'), {
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    })
}
