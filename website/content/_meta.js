export default {
    index: {
        type: 'page',
        title: 'Bref',
        display: 'hidden',
        // v2 used theme.layout: 'raw' (REMOVED in v4). Full-width + strip docs chrome:
        theme: {
            layout: 'full',
            sidebar: false,
            toc: false,
            breadcrumb: false,
            pagination: false,
            timestamp: false,
            footer: true,
        },
    },
    docs: {
        type: 'page',
        title: 'Documentation',
    },
}
