const withNextra = require('nextra')({
    theme: 'nextra-theme-docs',
    themeConfig: './theme.config.jsx',
    // Show the copy button on all code blocks
    // https://nextra.site/docs/guide/syntax-highlighting#copy-button
    defaultShowCopyCode: true,
})

module.exports = withNextra({
    // Redirect old .html links
    async redirects() {
        return [
            {
                source: '/docs/:path*.html',
                destination: '/docs/:path*',
                // TODO enable permanent redirect when all pages are migrated
                permanent: false,
                // permanent: true,
            },
        ]
    },
})

// If you have other Next.js configurations, you can pass them as the parameter:
// module.exports = withNextra({ /* other next.js config */ })
