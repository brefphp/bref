'use client'

import { Tabs } from 'nextra/components'
import { useEffect, useState } from 'react'

// Remembers the selected tab by its label: tab groups with the same labels
// (in any order) stay in sync across pages, e.g. "Laravel" or "Bref Cloud".
export function PersistentTabs({ items, ...props }) {
    const storageKey = 'bref-tabs:' + [...items].sort().join('|')
    const [selectedIndex, setSelectedIndex] = useState(0)

    useEffect(() => {
        const select = (label) => {
            const index = items.indexOf(label)
            if (index !== -1) setSelectedIndex(index)
        }
        try {
            select(localStorage.getItem(storageKey))
        } catch {}
        const onStorage = (event) => {
            if (event.key === storageKey) select(event.newValue)
        }
        window.addEventListener('storage', onStorage)
        return () => window.removeEventListener('storage', onStorage)
    }, [storageKey])

    const onChange = (index) => {
        setSelectedIndex(index)
        try {
            localStorage.setItem(storageKey, items[index])
        } catch {}
        // The `storage` event only fires in other browser tabs: notify the tab groups of this page too
        window.dispatchEvent(new StorageEvent('storage', { key: storageKey, newValue: items[index] }))
    }

    return <Tabs items={items} {...props} selectedIndex={selectedIndex} onChange={onChange} />
}
