import { CloudArrowUpIcon, LockClosedIcon, ServerIcon } from '@heroicons/react/20/solid'

const features = [
    {
        name: 'Push to deploy.',
        description:
            'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Maiores impedit perferendis suscipit eaque, iste dolor cupiditate blanditiis ratione.',
        icon: CloudArrowUpIcon,
    },
    {
        name: 'SSL certificates.',
        description: 'Anim aute id magna aliqua ad ad non deserunt sunt. Qui irure qui lorem cupidatat commodo.',
        icon: LockClosedIcon,
    },
    {
        name: 'Database backups.',
        description: 'Ac tincidunt sapien vehicula erat auctor pellentesque rhoncus. Et magna sit morbi lobortis.',
        icon: ServerIcon,
    },
]

export default function HowItWorks() {
    return (
        <div className="overflow-hidden home-container home-section">
            <div className="grid grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:grid-cols-2 lg:items-center">
                <div className="px-6 lg:px-0 lg:pr-4 lg:pt-4">
                    <div className="mx-auto max-w-2xl lg:mx-0 lg:max-w-lg">
                        <h2 className="mt-2 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                            How it works
                        </h2>
                        <p className="mt-6 text-lg leading-8 text-gray-600">
                            Lorem ipsum, dolor sit amet consectetur adipisicing elit. Maiores impedit perferendis suscipit eaque,
                            iste dolor cupiditate blanditiis ratione.
                        </p>
                        <dl className="mt-10 max-w-xl space-y-8 text-base leading-7 text-gray-600 lg:max-w-none">
                            {features.map((feature) => (
                                <div key={feature.name} className="relative pl-9">
                                    <dt className="inline font-semibold text-gray-900">
                                        <feature.icon className="absolute left-1 top-1 h-5 w-5 text-blue-500" aria-hidden="true" />
                                        {feature.name}
                                    </dt>{' '}
                                    <dd className="inline">{feature.description}</dd>
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
                                        <span className="text-red-500">✔</span> Service deployed to stack demo-prod <span className="text-gray-500">(31s)</span>
                                    </p>
                                    <p>&nbsp;</p>
                                    <p><span className="text-gray-500">endpoint:</span> https://yti4le2q5.lambda-url.us-east-1.on.aws/</p>
                                    <p className="text-gray-500">functions:</p>
                                    <p className="ml-5">api: demo-prod-api <span className="text-gray-500">(750 KB)</span></p>
                                    <p className="ml-5">cron: demo-prod-cron <span className="text-gray-500">(750 KB)</span></p>
                                    <p className="ml-5">worker: demo-prod-worker <span className="text-gray-500">(750 KB)</span></p>
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
