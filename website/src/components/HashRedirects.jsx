'use client'

import { useEffect } from 'react'
import { usePathname, useRouter } from 'next/navigation'
import { redirects } from '../../redirects'

// Ports the client-side redirect logic that lived in src/pages/_app.jsx.
// The server never sees the URL hash, so hash-anchor redirects (e.g.
// /docs/runtimes#bref-ping and /#ecosystem) must be resolved on the client.
// Non-hash entries are already handled server-side by next.config redirects().
export default function HashRedirects() {
    const router = useRouter()
    const pathname = usePathname()

    useEffect(() => {
        const check = () => {
            const asPath = window.location.pathname + window.location.hash
            const target = redirects[asPath]
            if (target) {
                router.replace(target)
            }
        }
        check()
        window.addEventListener('hashchange', check)
        return () => window.removeEventListener('hashchange', check)
    }, [pathname, router])

    return null
}
