import fs from 'fs'
import path from 'path'

// App Router Route Handler replacing pages/api/md/[...slug].js.
// Reads raw MDX from content/docs and strips imports + YAML frontmatter
// (NextSeo is gone in v4, so the old <NextSeo> stripper is replaced by a frontmatter strip).
export async function GET(req, { params }) {
    const { slug } = await params
    const slugPath = Array.isArray(slug) ? slug.join('/') : (slug ?? '')

    const docsDir = path.join(process.cwd(), 'content/docs')
    const candidates = slugPath
        ? [
              path.join(docsDir, `${slugPath}.mdx`),
              path.join(docsDir, `${slugPath}.md`),
              path.join(docsDir, slugPath, 'index.mdx'),
          ]
        : [path.join(docsDir, 'index.mdx')]

    // Reject any path that escapes content/docs (e.g. via `..` segments).
    const resolvedDocsDir = path.resolve(docsDir) + path.sep
    let filePath = candidates.find(
        candidate => path.resolve(candidate).startsWith(resolvedDocsDir) && fs.existsSync(candidate)
    )
    if (!filePath) {
        return new Response(JSON.stringify({ error: 'Page not found' }), {
            status: 404,
            headers: { 'Content-Type': 'application/json' },
        })
    }

    let content = fs.readFileSync(filePath, 'utf8')
    // Strip YAML frontmatter
    content = content.replace(/^---\n[\s\S]*?\n---\n/, '')
    // Strip import statements
    content = content.replace(/^import\s+.*?(?:from\s+['"].*?['"])?;?\s*$/gm, '')
    // Strip JSX comments (invisible when rendered, but not valid Markdown)
    content = content.replace(/\{\/\*[\s\S]*?\*\/\}\n?/g, '')
    // Clean up excessive blank lines at the start
    content = content.replace(/^\s*\n+/, '')

    return new Response(content, {
        status: 200,
        headers: { 'Content-Type': 'text/markdown; charset=utf-8' },
    })
}
