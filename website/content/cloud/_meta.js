// These are the bare /cloud/* fragment routes kept for v2 URL parity (each renders a
// single Bref Cloud page section in isolation). They must never get docs chrome.
const theme = {
    layout: 'full',
    sidebar: false,
    toc: false,
    breadcrumb: false,
    pagination: false,
    timestamp: false,
    copyPage: false,
}

export default {
    'case-studies': { theme },
    faq: { theme },
    features: { theme },
    'hero-bg': { theme },
    'how-it-works': { theme },
    pricing: { theme },
}
