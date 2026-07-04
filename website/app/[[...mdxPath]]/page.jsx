import { generateStaticParamsFor, importPage } from 'nextra/pages'
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
    return {
        ...metadata,
        ...(isHome
            ? { title: { absolute: 'Bref – Simple and scalable PHP with serverless' } }
            : {}),
        openGraph: {
            images: [{ url: 'https://bref.sh/social-card.png' }],
            ...(metadata.openGraph || {}),
        },
        // Per-docs-page alternate markdown link (was theme.config.head conditional)
        ...(isDocsPage
            ? { alternates: { types: { 'text/markdown': `/docs/${mdxPath.slice(1).join('/')}.md` } } }
            : {}),
    }
}

const Wrapper = getMDXComponents().wrapper

export default async function Page(props) {
    const params = await props.params
    const { default: MDXContent, toc, metadata, sourceCode } = await importPage(params.mdxPath)
    return (
        <Wrapper toc={toc} metadata={metadata} sourceCode={sourceCode}>
            <MDXContent {...props} params={params} />
        </Wrapper>
    )
}
