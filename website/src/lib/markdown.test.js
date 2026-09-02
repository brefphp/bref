import { test } from 'node:test'
import assert from 'node:assert/strict'
import { toMarkdown, findSourceFile } from './markdown.js'

// Run with `npm test`

test('strips frontmatter, imports and JSX comments', () => {
    const source = `---
title: Hello
---

import { Callout } from 'nextra/components'
import img from './img.png';

{/* not visible */}
# Hello
`
    assert.equal(toMarkdown(source, 'docs/hello'), '# Hello\n')
})

test('makes links absolute, resolved from the file location', () => {
    const cases = [
        ['[a](./getting-started.mdx)', '[a](https://bref.sh/docs/laravel/getting-started)'],
        ['[a](../symfony/getting-started.mdx#queues)', '[a](https://bref.sh/docs/symfony/getting-started#queues)'],
        ['[a](../laravel.mdx "Title")', '[a](https://bref.sh/docs/laravel "Title")'],
        ['[a](/docs/deploy.md#production)', '[a](https://bref.sh/docs/deploy#production)'],
        ['[a](/cloud)', '[a](https://bref.sh/cloud)'],
        ['[a](#anchor)', '[a](#anchor)'],
        ['[a](https://example.com/page.md)', '[a](https://example.com/page.md)'],
        ['[a](mailto:hi@bref.sh)', '[a](mailto:hi@bref.sh)'],
        ['<Cards.Card href="./queues.mdx" />', '<Cards.Card href="https://bref.sh/docs/laravel/queues" />'],
    ]
    for (const [input, expected] of cases) {
        assert.equal(toMarkdown(input, 'docs/laravel/index'), expected)
    }
})

test('leaves code blocks and inline code untouched', () => {
    const source = `Intro [link](./a.mdx)

\`\`\`js
import { Stack } from 'aws-cdk-lib';
const html = '<a href="/foo">[x](./b.mdx)</a>';
\`\`\`

Inline \`href="/bar"\` and \`[x](./c.mdx)\` stay, but [link](./d.mdx) changes.
`
    const expected = `Intro [link](https://bref.sh/docs/a)

\`\`\`js
import { Stack } from 'aws-cdk-lib';
const html = '<a href="/foo">[x](./b.mdx)</a>';
\`\`\`

Inline \`href="/bar"\` and \`[x](./c.mdx)\` stay, but [link](https://bref.sh/docs/d) changes.
`
    assert.equal(toMarkdown(source, 'docs/index'), expected)
})

test('only resolves pages under the allowed content roots', () => {
    assert.match(findSourceFile('docs/serverless-costs'), /content\/docs\/serverless-costs\.mdx$/)
    assert.match(findSourceFile('docs/setup'), /content\/docs\/setup\/index\.mdx$/)
    assert.match(findSourceFile('/docs/'), /content\/docs\/index\.mdx$/)
    assert.equal(findSourceFile('docs/does-not-exist'), null)
    assert.equal(findSourceFile('support'), null)
    assert.equal(findSourceFile('docs/../index'), null)
    assert.equal(findSourceFile('docs/../../package'), null)
})
