'use client'

import { useEffect, useRef } from 'react'
import { Search } from 'nextra/components'
import { usePlausible } from 'next-plausible'

// Search analytics via Plausible custom events (replaces the Algolia DocSearch
// analytics we lost when migrating to Pagefind):
// - `Docs Search` — a query the user settled on (debounced, so roughly one event
//   per real search instead of one per keystroke)
// - `Docs Search No Results` — same, when the query matched nothing: the queries
//   the docs don't answer, which is the actionable half of search analytics.
// Both carry a `query` prop. They need matching custom event goals in the
// Plausible dashboard to show up in reports.
const DEBOUNCE_MS = 1500
const MIN_QUERY_LENGTH = 3

// Set while Nextra renders the empty-result state (see <NoResults> below).
const state = { noResults: false }

function NoResults() {
    useEffect(() => {
        state.noResults = true
        return () => {
            state.noResults = false
        }
    }, [])
    return 'No results found.'
}

export default function DocsSearch() {
    const plausible = usePlausible()
    const timer = useRef(undefined)
    const lastSent = useRef(undefined)

    const onSearch = (query) => {
        clearTimeout(timer.current)
        const trimmed = query.trim()
        if (trimmed.length < MIN_QUERY_LENGTH || trimmed === lastSent.current) return
        timer.current = setTimeout(() => {
            lastSent.current = trimmed
            plausible('Docs Search', { props: { query: trimmed } })
            // By the time the debounce fires, Pagefind (client-side, fast) has
            // long settled, so the empty-state flag reflects this query.
            if (state.noResults) {
                plausible('Docs Search No Results', { props: { query: trimmed } })
            }
        }, DEBOUNCE_MS)
    }

    // Don't leak a pending event when the component unmounts mid-debounce.
    useEffect(() => () => clearTimeout(timer.current), [])

    return <Search placeholder="Search docs" onSearch={onSearch} emptyResult={<NoResults />} />
}
