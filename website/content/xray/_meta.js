// The parent `xray` entry uses `layout: 'full'` (custom landing page), which
// cascades to child pages and would strip all markdown styling.
export default {
    docs: {
        theme: {
            layout: 'default',
            typesetting: 'default',
        },
    },
    changelog: {
        theme: {
            layout: 'default',
            typesetting: 'default',
        },
    },
    terms: {
        theme: {
            layout: 'default',
            typesetting: 'default',
        },
    },
};
