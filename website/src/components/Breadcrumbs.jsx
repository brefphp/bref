import { ChevronRightIcon } from '@heroicons/react/20/solid'

/**
 * @param {Array<{name: string; href: string; current: boolean}>} pages
 */
export default function Breadcrumbs({ pages }) {
    return (
        <nav className="flex" aria-label="Breadcrumb">
            <ol role="list" className="flex items-center space-x-2">
                <li>
                    <a href="/" className="text-gray-400 hover:text-gray-500">
                        Bref
                    </a>
                </li>
                {pages.map((page) => (
                    <li key={page.name}>
                        <div className="flex items-center">
                            <ChevronRightIcon className="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true" />
                            <a
                                href={page.href}
                                className="ml-2 text-sm font-medium text-gray-600 hover:text-gray-800"
                            >
                                {page.name}
                            </a>
                        </div>
                    </li>
                ))}
            </ol>
        </nav>
    )
}
