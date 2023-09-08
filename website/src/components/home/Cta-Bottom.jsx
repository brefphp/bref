export default function CtaBottom() {
    return (
        <div className="bg-gray-900">
            <div className="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:flex lg:items-center lg:justify-between lg:px-8">
                <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Ready to dive in?
                    <br />
                    Get started in under a minute.
                </h2>
                <div className="mt-10 flex items-center gap-x-6 lg:mt-0 lg:flex-shrink-0">
                    <a
                        href="/docs/"
                        className="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    >
                        Documentation
                    </a>
                    <a href="/plans" className="text-sm font-semibold leading-6 text-white">
                        Support & consulting <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    )
}
