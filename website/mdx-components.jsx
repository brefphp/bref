import { useMDXComponents as getDocsMDXComponents } from 'nextra-theme-docs'
import { Callout, withGitHubAlert } from 'nextra/components'

// Same mapping as nextra-theme-docs (dist/mdx-components/index.js CALLOUT_TYPE)
const CALLOUT_TYPE = {
    caution: 'error',
    important: 'important',
    note: 'info',
    tip: 'default',
    warning: 'warning',
}

const docsComponents = getDocsMDXComponents({
    // Custom h1 override (was theme.config.components.h1 in v2)
    h1: (props) => (
        <h1
            className="mt-2 text-4xl font-black tracking-tight text-slate-900 dark:text-slate-100"
            {...props}
        />
    ),
    // Same as the theme's default GitHub-alert blockquote, except we drop the bold
    // "Tip"/"Note"/"Warning" label that withGitHubAlert injects as the first child:
    // our callouts historically have no label. Plain (non-alert) blockquotes keep the
    // theme's styling (classes copied from the theme's non-exported Blockquote).
    blockquote: withGitHubAlert(
        ({ type, children, ...props }) => (
            <Callout type={CALLOUT_TYPE[type]} {...props}>
                {children.slice(1)}
            </Callout>
        ),
        (props) => (
            <blockquote
                className="x:not-first:mt-[1.25em] x:border-gray-300 x:italic x:text-gray-700 x:dark:border-gray-700 x:dark:text-gray-400 x:border-s-2 x:ps-[1.5em]"
                {...props}
            />
        ),
    ),
})

export const useMDXComponents = (components) => ({
    ...docsComponents,
    ...components,
})
