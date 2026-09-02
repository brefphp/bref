import { getPageMap } from 'nextra/page-map'
import { normalizePages } from 'nextra/normalize-pages'

/**
 * List the documentation pages in sidebar order, grouped by the sidebar
 * sections (the separators in content/docs/_meta.js).
 *
 * Returns [{ title: 'Getting started', pages: [{ title, route, description }] }]
 */
export async function getDocsSections() {
    const { docsDirectories } = normalizePages({
        list: await getPageMap(),
        route: '/docs',
    })

    const sections = [{ title: null, pages: [] }]
    const visit = (items, parentTitle) => {
        for (const item of items) {
            if (item.display === 'hidden') continue
            if (item.type === 'separator') {
                sections.push({ title: item.title, pages: [] })
                continue
            }
            if (item.route && 'frontMatter' in item) {
                sections.at(-1).pages.push({
                    title: parentTitle && item.name !== 'index' ? `${parentTitle} - ${item.title}` : item.title,
                    route: item.route,
                    description: item.frontMatter?.description ?? '',
                })
            }
            if (item.children) {
                visit(item.children, item.title)
            }
        }
    }
    visit(docsDirectories, null)

    return sections.filter(section => section.pages.length > 0)
}

/**
 * List the news articles, newest first.
 */
export async function getNewsPages() {
    const pages = (await getPageMap('/news'))
        // Skip the /news index page, keep the articles
        .filter(item => item.route && item.route !== '/news' && 'frontMatter' in item)
        .map(item => ({
            title: item.frontMatter?.title ?? item.title,
            route: item.route,
            description: item.frontMatter?.description ?? '',
        }))
    // Article file names start with a number (01-bref-1.0, 02-bref-2.0...): newest first
    return pages.sort((a, b) => b.route.localeCompare(a.route))
}
