import { generateStaticParamsFor, importPage } from 'nextra/pages'
import { getPageMap } from 'nextra/page-map'
import { normalizePages } from 'nextra/normalize-pages'
import { useMDXComponents as getMDXComponents } from '../../mdx-components'

export const generateStaticParams = generateStaticParamsFor('mdxPath')

// All content pages are prerendered at build time. Any path not produced by
// generateStaticParams renders the not-found boundary (app/not-found.jsx) with
// a 404, instead of the optional catch-all swallowing unknown URLs.
export const dynamicParams = false

// ISR route-segment config on the catch-all. Applies to ALL content/ MDX routes.
// Per-datasource control is better done via fetch(url, { next: { revalidate } }).
export const revalidate = 3600

// Title template + per-page description/OG.
// importPage returns `metadata` built from MDX frontmatter (title/description).
// The root `metadata.title.template` applies `%s – Bref` automatically; the home
// page needs an absolute title to avoid a doubled "– Bref" suffix.
export async function generateMetadata(props) {
    const params = await props.params
    const mdxPath = params.mdxPath ?? []
    const { metadata } = await importPage(mdxPath)
    const isHome = mdxPath.length === 0
    const isDocsPage = mdxPath[0] === 'docs' && mdxPath.length > 1
    // `seoTitle` frontmatter sets the exact <title> (no "– Bref" suffix) without
    // affecting the sidebar label; `ogImage` overrides the default social card.
    const seoTitle = metadata.seoTitle
    const ogImage = metadata.ogImage
    return {
        ...metadata,
        ...(isHome
            ? { title: { absolute: 'Bref – Simple and scalable PHP with serverless' } }
            : {}),
        ...(seoTitle ? { title: { absolute: seoTitle } } : {}),
        openGraph: {
            images: [{ url: ogImage ?? 'https://bref.sh/social-card.png' }],
            ...(metadata.openGraph || {}),
        },
        // Per-docs-page alternate markdown link (was theme.config.head conditional)
        ...(isDocsPage
            ? { alternates: { types: { 'text/markdown': `/docs/${mdxPath.slice(1).join('/')}.md` } } }
            : {}),
    }
}

const Wrapper = getMDXComponents().wrapper

// "Edit this page" URLs: metadata.filePath points into content/, but content/docs is
// a build-time copy of the real sources in <repo>/docs (and the rest of content/ lives
// under website/ in the repo), so docsRepositoryBase + filePath never matches a real
// GitHub file. The theme uses filePath verbatim when it is already a full URL
// (toc.js), so rewrite it to the true source location.
const GITHUB_BLOB = 'https://github.com/brefphp/bref/blob/master'
function editUrl(filePath) {
    if (!filePath?.startsWith('content/')) return filePath
    return filePath.startsWith('content/docs/')
        ? `${GITHUB_BLOB}/docs/${filePath.slice('content/docs/'.length)}`
        : `${GITHUB_BLOB}/website/${filePath}`
}

export default async function Page(props) {
    const params = await props.params
    const mdxPath = params.mdxPath ?? []
    const { default: MDXContent, toc, metadata, sourceCode } = await importPage(params.mdxPath)

    // Marketing pages set `theme.layout: 'full'` in _meta.js to render edge-to-edge
    // (home, cloud, support, ...). In Nextra v4 `layout: 'full'` only drops the TOC
    // reservation — the theme's MDX wrapper still constrains content to
    // --nextra-content-width (90rem). Resolve the active layout the same way the
    // theme does (normalizePages) and tag full pages so globals.css can unconstrain
    // the content-width container and remove the article's horizontal padding,
    // letting hero backgrounds bleed to the viewport edges. Navbar/footer keep the
    // 90rem inner width because they are not descendants of this container.
    const { activeThemeContext } = normalizePages({
        list: await getPageMap(),
        route: '/' + mdxPath.join('/'),
    })
    const fullLayoutProps = activeThemeContext.layout === 'full' ? { 'data-full-layout': '' } : {}

    return (
        <Wrapper
            toc={toc}
            metadata={{ ...metadata, filePath: editUrl(metadata.filePath) }}
            sourceCode={sourceCode}
            {...fullLayoutProps}
        >
            <MDXContent {...props} params={params} />
        </Wrapper>
    )
}
