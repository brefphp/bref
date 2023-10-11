const { withPlausibleProxy } = require('next-plausible')

const withNextra = require('nextra')({
    theme: 'nextra-theme-docs',
    themeConfig: './theme.config.jsx',
    // Show the copy button on all code blocks
    // https://nextra.site/docs/guide/syntax-highlighting#copy-button
    defaultShowCopyCode: true,
})

module.exports = withNextra(withPlausibleProxy()({
    // Redirect old .html links
    async redirects() {
        const { redirects } = require('./redirects');
        const redirectList = Object.entries(redirects)
            .map(([source, destination]) => ({
                source,
                destination,
                permanent: true,
            }));
        return [
            {
                source: '/docs/:path*.html',
                destination: '/docs/:path*',
                permanent: true,
            },
            ...redirectList,
        ]
    },
}));

// If you have other Next.js configurations, you can pass them as the parameter:
// module.exports = withNextra({ /* other next.js config */ })
