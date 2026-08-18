const posts = [
    {
        id: 2,
        title: 'Treezor: a serverless banking platform',
        href: '/docs/case-studies/treezor',
        description:
            'Treezor, a banking-as-a-service platform processing millions of transactions daily, successfully migrated its legacy PHP application to serverless  using AWS Lambda and Bref. This transformation improved scalability, reduced response times by 2.5x, cut production alerts by up to 3x, and reduced transaction timeouts 10x.',
    },
    {
        id: 3,
        title: 'How Craft CMS built Craft Cloud',
        href: '/docs/case-studies/craft-cloud',
        description:
            'Craft CMS built Craft Cloud, a serverless hosting platform to run Craft CMS projects at scale using AWS and Cloudflare. It uses Bref and AWS Lambda to host PHP applications, with each customer project isolated in its own environment and container. With over 310 million HTTP requests handled in a month, Craft Cloud proves that running PHP serverless is fast, scalable, and production-ready.',
    },
];

export default function CaseStudies() {
    return (
        <div className="bg-gray-900 py-24 sm:py-32">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="grid grid-cols-1 items-center gap-x-8 gap-y-16 lg:grid-cols-2">
                    <div className="mx-auto w-full lg:mx-0">
                        <h2 className="text-3xl font-black leading-8 text-white">
                            How teams use Bref to scale
                        </h2>
                        <p className="mt-6 text-lg leading-7 text-gray-300">
                            Discover how teams use Bref to scale their PHP applications with serverless. From reducing costs to improving performance, serverless is simplifying the way developers build and deploy applications.
                        </p>
                        <div className="mt-8 flex items-center gap-x-6">
                            <a
                                href="/docs/"
                                className="rounded-md bg-blue-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
                            >
                                Documentation
                            </a>
                            <a href="/support" className="text-sm font-semibold text-white">
                                Support & consulting <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                        <form action="https://bref.mailcoach.app/subscribe/be83c960-8fee-43fa-abfa-29669e9f433a"
                              className="w-full flex flex-col items-start mt-8"
                              method="post">
                            <input type="hidden" name="tags" value="bref_website" />
                            <div className="flex flex-col sm:flex-row w-full sm:w-auto gap-2">
                                <input
                                    className="min-w-0 flex-auto rounded-md border-0 bg-white/10 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-white/75 focus:ring-2 focus:ring-inset focus:ring-white sm:text-sm sm:leading-6"
                                    name="email" aria-label="Email" placeholder="you@example.com" required type="email" />
                                <button type="submit"
                                        className="flex-none rounded-md !bg-gray-500 hover:!bg-gray-400 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                                    Subscribe to the newsletter
                                </button>
                            </div>
                        </form>
                    </div>
                    <div className="mx-auto w-full border-t border-gray-900/10 pt-12 sm:pt-16 lg:mx-0 lg:max-w-none lg:border-t-0 lg:pt-0 text-gray-300">
                        <div className="-my-4 divide-y divide-gray-900/10">
                            {posts.map((post) => (
                                <article key={post.id} className="py-6">
                                    <div className="group relative max-w-xl">
                                        <h2 className="mt-2 text-lg font-black text-gray-200 group-hover:text-gray-100">
                                            <a href={post.href}>
                                                <span className="absolute inset-0" />
                                                {post.title}
                                            </a>
                                        </h2>
                                        <p className="mt-4 text-sm/6">{post.description}</p>
                                        <div className="mt-4 flex flex-col justify-between gap-6 sm:mt-8 sm:flex-row-reverse sm:gap-8 lg:mt-4 lg:flex-col">
                                            <a href={post.href} className="text-sm/6 font-semibold text-blue-500 group-hover:text-blue-400">
                                                Read more <span aria-hidden="true">&rarr;</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
