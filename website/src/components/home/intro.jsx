import { CloudArrowUpIcon, PresentationChartLineIcon, BanknotesIcon } from '@heroicons/react/20/solid'

const features = [
    {
        name: 'Simple.',
        description:
            'Instead of setting up and maintaining servers, define your application in a simple <code class="inline-code">serverless.yml</code> file. Then deploy to AWS with <code class="inline-code">serverless deploy</code>. Bref integrates with the <a href="https://www.serverless.com/framework" class="link">Serverless Framework</a> for a great developer experience.',
        icon: CloudArrowUpIcon,
    },
    {
        name: 'Scalable.',
        description: 'Bref provides open-source runtimes to run PHP on AWS Lambda. AWS Lambda runs your code redundantly across data centers and scales in real-time. All automatically. Handle 1 request/second or 1000 with the same code.',
        icon: PresentationChartLineIcon,
    },
    {
        name: 'Cost-efficient.',
        description: 'Instead of paying for servers that are idle most of the time, pay for the time the code is actually running. The AWS free tier even provides about 1 million free requests per month. Play with the <a href="/docs/serverless-costs" class="link">serverless costs calculator</a>.',
        icon: BanknotesIcon,
    },
]

export default function Intro() {
    const date = new Date();
    const year = date.getFullYear();

    return (
        <div className="overflow-hidden home-container home-section">
            <div className="grid grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:grid-cols-2 lg:items-center">
                <div className="px-6 lg:px-0 lg:pr-4 lg:pt-4">
                    <div className="mx-auto max-w-2xl lg:mx-0 lg:max-w-lg">
                        <h2 className="mt-2 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                            Why Bref? Why serverless?
                        </h2>
                        <p className="mt-6 text-lg leading-8 text-gray-600">
                            We're in {year}.
                            Applications should <strong>scale</strong> automatically.
                            Hosting should be <strong>reliable</strong> and <strong>cost-efficient</strong>.
                            Infrastructure should accelerate development, not consume our time.
                        </p>
                        <p className="mt-6 text-gray-600">
                            Bref deploys PHP applications to {' '}
                            <a href="https://aws.amazon.com/lambda/" className="link">AWS Lambda</a> {' '}
                            and sets up the rest of the infrastructure using serverless services.
                        </p>
                        <dl className="mt-10 max-w-xl space-y-8 text-base leading-7 text-gray-600 lg:max-w-none">
                            {features.map((feature) => (
                                <div key={feature.name} className="relative pl-9">
                                    <dt className="inline font-semibold text-gray-900">
                                        <feature.icon className="absolute left-1 top-1 h-5 w-5 text-blue-500" aria-hidden="true" />
                                        {feature.name}
                                    </dt>{' '}
                                    <dd className="inline" dangerouslySetInnerHTML={{ __html: feature.description }}></dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
                <div className="sm:px-6 lg:px-0">
                    <div className="relative isolate overflow-hidden bg-blue-500 px-6 pt-8 sm:mx-auto sm:max-w-2xl sm:rounded-3xl sm:pl-16 sm:pr-0 sm:pt-16 lg:mx-0 lg:max-w-none">
                        <div
                            className="absolute -inset-y-px -left-3 -z-10 w-full origin-bottom-left skew-x-[-30deg] bg-blue-100 opacity-20 ring-1 ring-inset ring-white"
                            aria-hidden="true"
                        />
                        <div className="mx-auto max-w-2xl sm:mx-0 sm:max-w-none">
                            <div className="w-screen overflow-hidden rounded-tl-xl bg-gray-900">
                                <div className=" px-6 pb-14 pt-6 text-gray-200 font-mono text-sm">
                                    <p><span className="text-gray-400">$</span> serverless deploy</p>
                                    <p>&nbsp;</p>
                                    <p>Deploying demo to stage dev <span className="text-gray-500">(us-east-1)</span></p>
                                    <p>&nbsp;</p>
                                    <p>
                                        <span className="text-red-500">✔</span> Service deployed to stack demo-dev <span className="text-gray-500">(31s)</span>
                                    </p>
                                    <p>&nbsp;</p>
                                    <p><span className="text-gray-500">endpoint:</span> https://yti4le2q5.lambda-url.us-east-1.on.aws/</p>
                                    <p className="text-gray-500">functions:</p>
                                    <p className="ml-5">web: demo-dev-web <span className="text-gray-500">(750 KB)</span></p>
                                    <p className="ml-5">cron: demo-dev-cron <span className="text-gray-500">(750 KB)</span></p>
                                    <p className="ml-5">worker: demo-dev-worker <span className="text-gray-500">(750 KB)</span></p>
                                </div>
                            </div>
                        </div>
                        <div
                            className="pointer-events-none absolute inset-0 ring-1 ring-inset ring-black/10 sm:rounded-3xl"
                            aria-hidden="true"
                        />
                    </div>
                </div>
            </div>
        </div>
    )
}
