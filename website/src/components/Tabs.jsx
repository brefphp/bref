import { Tabs as NextraTabs } from 'nextra/components'
import { PersistentTabs } from './PersistentTabs'

// Drop-in replacement for Nextra's `Tabs` that remembers the selected tab across pages.
// Not a client component, so that MDX pages can use `<Tabs.Tab>` (server components cannot dot into client modules).
export const Tabs = Object.assign((props) => <PersistentTabs {...props} />, {
    Tab: NextraTabs.Tab,
})
