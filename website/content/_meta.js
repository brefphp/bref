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
    // The following pages are ported from `_port/pages/_meta.json` but are not
    // built yet (Phase C: marketing/standalone pages). Keep them commented out
    // so the nav doesn't link to 404s until their content is ported.
    // news: {
    //     title: 'News',
    //     type: 'page',
    //     display: 'hidden',
    //     theme: { typesetting: 'article' },
    // },
    // cloud: {
    //     type: 'page',
    //     title: 'Bref Cloud',
    //     theme: { layout: 'full', sidebar: false, toc: false, breadcrumb: false, pagination: false, timestamp: false },
    // },
    // support: {
    //     type: 'page',
    //     title: 'Support',
    //     theme: { layout: 'full', sidebar: false, toc: false, breadcrumb: false, pagination: false, timestamp: false },
    // },
    // credits: {
    //     type: 'page',
    //     title: 'Credits',
    //     display: 'hidden',
    // },
    // 404: {
    //     type: 'page',
    //     title: '404',
    //     display: 'hidden',
    //     theme: { layout: 'full', sidebar: false, toc: false, breadcrumb: false, pagination: false, timestamp: false },
    // },
    // sentry: {
    //     type: 'page',
    //     title: 'Sentry integration for PHP on AWS Lambda',
    //     display: 'hidden',
    //     theme: { layout: 'full', sidebar: false, toc: false, breadcrumb: false, pagination: false, timestamp: false },
    // },
    // xray: {
    //     type: 'page',
    //     title: 'X-Ray integration for PHP',
    //     display: 'hidden',
    //     theme: { layout: 'full', sidebar: false, toc: false, breadcrumb: false, pagination: false, timestamp: false },
    // },
}
