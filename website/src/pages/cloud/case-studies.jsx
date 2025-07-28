const featuredPost = {
    id: 1,
    title: 'How teams use Bref to scale',
    href: '#',
    description:
        'Discover how teams use Bref to scale their PHP applications with serverless. From reducing costs to improving performance, serverless is simplifying the way developers build and deploy applications.',
}
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
]

export default function CaseStudies() {
    return (
        <div className="bg-white py-24 sm:py-32">
            <div className="mx-auto grid max-w-7xl grid-cols-1 gap-x-8 gap-y-12 px-6 sm:gap-y-16 lg:grid-cols-2 lg:px-8">
                <article className="mx-auto w-full max-w-2xl lg:mx-0 lg:max-w-lg">
                    <h2
                        id="featured-post"
                        className="mt-4 text-pretty text-3xl font-black tracking-tight text-gray-950 sm:text-4xl"
                    >
                        {featuredPost.title}
                    </h2>
                    <p className="mt-4 text-lg/8 text-gray-600">{featuredPost.description}</p>
                    <div className="mt-4 flex flex-col justify-between gap-6 sm:mt-8 sm:flex-row-reverse sm:gap-8 lg:mt-4 lg:flex-col">
                        <div className="flex">
                            <a href="/docs/case-studies" className="text-sm/6 font-semibold text-blue-600 hover:text-blue-500">
                                Read case studies <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </article>
                <div className="mx-auto w-full max-w-2xl border-t border-gray-900/10 pt-12 sm:pt-16 lg:mx-0 lg:max-w-none lg:border-t-0 lg:pt-0">
                    <div className="-my-4 divide-y divide-gray-900/10">
                        {posts.map((post) => (
                            <article key={post.id} className="py-6">
                                <div className="group relative max-w-xl">
                                    <h2 className="mt-2 text-lg font-black text-gray-900 group-hover:text-gray-600">
                                        <a href={post.href}>
                                            <span className="absolute inset-0" />
                                            {post.title}
                                        </a>
                                    </h2>
                                    <p className="mt-4 text-sm/6 text-gray-600">{post.description}</p>
                                    <div className="mt-4 flex flex-col justify-between gap-6 sm:mt-8 sm:flex-row-reverse sm:gap-8 lg:mt-4 lg:flex-col">
                                        <a href={post.href} className="text-sm/6 font-semibold text-blue-600 group-hover:text-blue-500">
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
    )
}
