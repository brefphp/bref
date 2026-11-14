import fs from 'fs'
import path from 'path'

export const SITE_URL = 'https://bref.sh'

const contentDir = path.join(process.cwd(), 'content')

// Top-level content folders whose pages are served as Markdown (for AI agents).
// The marketing pages (home, /cloud, /support...) are React components, not
// prose, so they are not exposed here.
const MARKDOWN_ROOTS = ['docs', 'news']

/**
 * Resolve a site route (e.g. "docs/laravel/getting-started", "docs", "news/04-august-2026")
 * to its MDX source file, or null if none exists.
 */
export function findSourceFile(route) {
    // Normalize first so that `..` segments cannot escape the allowed roots
    const clean = path.posix.normalize('/' + route).replace(/^\/+|\/+$/g, '')
    const root = clean.split('/')[0]
    if (!MARKDOWN_ROOTS.includes(root)) return null

    const candidates = [
        path.join(contentDir, `${clean}.mdx`),
        path.join(contentDir, `${clean}.md`),
        path.join(contentDir, clean, 'index.mdx'),
    ]
    // Reject any path that escapes content/ (e.g. via `..` segments).
    const resolvedContentDir = path.resolve(contentDir) + path.sep
    return (
        candidates.find(
            candidate => path.resolve(candidate).startsWith(resolvedContentDir) && fs.existsSync(candidate)
        ) ?? null
    )
}

/**
 * Turn the URL of a page (relative to the site root, without leading slash)
 * into the canonical HTML URL of that page.
 */
export function routeToUrl(route) {
    const clean = route.replace(/^\/+|\/+$/g, '')
    return clean ? `${SITE_URL}/${clean}` : SITE_URL
}

/**
 * Rewrite a link target found in a MDX file into an absolute URL.
 * `fileRoute` is the route of the file that contains the link (e.g. "docs/laravel/index").
 */
function absoluteUrl(target, fileRoute) {
    if (/^(https?:|mailto:|#|data:)/.test(target)) return target

    const [pathPart, hash] = target.split('#')
    // Relative links are resolved from the directory of the file
    let resolved = pathPart.startsWith('/')
        ? pathPart
        : path.posix.join(path.posix.dirname('/' + fileRoute), pathPart)
    // `./getting-started.mdx` -> `/docs/laravel/getting-started`
    resolved = resolved.replace(/\.mdx?$/, '').replace(/\/index$/, '')
    return `${SITE_URL}${resolved}${hash ? '#' + hash : ''}`
}

/**
 * Convert the raw MDX source of a page into Markdown suitable for AI agents:
 * strips the frontmatter, imports and JSX comments, and makes all links absolute.
 * The occasional JSX component (tabs, cards...) is left as is.
 *
 * `fileRoute` is the route of the source file relative to content/, without
 * extension (e.g. "docs/laravel/index").
 */
export function toMarkdown(source, fileRoute) {
    let content = source
    // Strip YAML frontmatter
    content = content.replace(/^---\n[\s\S]*?\n---\n/, '')
    // Strip JSX comments (invisible when rendered, but not valid Markdown)
    content = content.replace(/\{\/\*[\s\S]*?\*\/\}\n?/g, '')

    // The rest only applies outside of code blocks and inline code, so that code
    // samples (e.g. JS `import` statements or HTML links) are preserved.
    content = content
        .split(/(```[\s\S]*?```|`[^`\n]*`)/)
        .map((part, index) => (index % 2 === 1 ? part : transformProse(part, fileRoute)))
        .join('')

    // Clean up excessive blank lines at the start
    content = content.replace(/^\s*\n+/, '')
    return content
}

function transformProse(content, fileRoute) {
    // Strip import statements
    content = content.replace(/^import\s+.*?(?:from\s+['"].*?['"])?;?\s*$/gm, '')
    // Absolute URLs in Markdown links: [text](./page.mdx#anchor) -> [text](https://bref.sh/docs/page#anchor)
    content = content.replace(/\]\(([^)\s]+)((?:\s[^)]*)?)\)/g, (match, target, title) => {
        return `](${absoluteUrl(target, fileRoute)}${title})`
    })
    // Absolute URLs in JSX/HTML links: href="./page.mdx" -> href="https://bref.sh/docs/page"
    content = content.replace(/href="([^"]+)"/g, (match, target) => `href="${absoluteUrl(target, fileRoute)}"`)
    return content
}

/**
 * Read a page and return its Markdown, or null if the page has no source file.
 */
export function readMarkdown(route) {
    const filePath = findSourceFile(route)
    if (!filePath) return null
    const fileRoute = path.relative(contentDir, filePath).replace(/\.mdx?$/, '')
    return toMarkdown(fs.readFileSync(filePath, 'utf8'), fileRoute)
}
