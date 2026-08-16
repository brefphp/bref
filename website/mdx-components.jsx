import { useMDXComponents as getDocsMDXComponents } from 'nextra-theme-docs'

const docsComponents = getDocsMDXComponents({
    // Custom h1 override (was theme.config.components.h1 in v2)
    h1: (props) => (
        <h1
            className="mt-2 text-4xl font-black tracking-tight text-slate-900 dark:text-slate-100"
            {...props}
        />
    ),
})

export const useMDXComponents = (components) => ({
    ...docsComponents,
    ...components,
})
