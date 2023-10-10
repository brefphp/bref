import { Fragment } from 'react'
import { CheckIcon, MinusIcon } from '@heroicons/react/20/solid'

const tiers = [
    {
        name: 'OpenSource',
        id: 'open-source',
        href: '/docs/',
        buttonText: 'Get Started',
        priceMonthly: 'Free and Open Source',
        description: 'Bref',
        mostPopular: false,
    },
    {
        name: 'Pro',
        id: 'pro',
        href: 'https://matthieunapoli.typeform.com/to/d2OoXKln',
        buttonText: 'Get Started',
        priceMonthly: 100,
        description: 'Bref Pro',
        mostPopular: true,
    },
    {
        name: 'Enterprise',
        id: 'enterprise',
        href: 'mailto:enterprise@bref.sh?subject=Bref%20Enterprise',
        buttonText: 'Get in touch',
        priceMonthly: 'Get in touch',
        description: 'Bref Enterprise',
        mostPopular: false,
    },
]
const sections = [
    {
        name: 'Bref and AWS deployments',
        features: [
            { name: 'The Bref open-source project, its documentation and framework integrations', tiers: { OpenSource: true, Pro: true, Enterprise: true } },
            { name: 'Deploy your applications to your AWS account', tiers: { OpenSource: true, Pro: true, Enterprise: true } },
            { name: ' Tailor-made AWS Lambda runtimes and PHP extensions optimized for your project', tiers: { Enterprise: 'Optional' } },
            { name: 'Appear as an open-source sponsor 💙', tiers: { Pro: 'Gold sponsor', Enterprise: 'Premium sponsor' } },
        ],
    },
    {
        name: 'Resources and support',
        features: [
            { name: 'Priority support and bugfixes on GitHub', tiers: { Pro: true, Enterprise: true } },
            { name: 'Expert support via Slack and Email', tiers: { Pro: true, Enterprise: true } },
            { name: 'Architecture design and review in Zoom', tiers: { Enterprise: true } },
            { name: 'GitHub/GitLab infrastructure code review', tiers: { Enterprise: true } },
            { name: 'Unlimited access to the <a class="underline" href="https://serverless-visually-explained.com/">Serverless Visually Explained</a> course', tiers: { Enterprise: true } },
            { name: 'Onboarding workshop online or on-site', tiers: { Enterprise: 'Optional' } },
        ],
    },
]

function classNames(...classes) {
    return classes.filter(Boolean).join(' ')
}

export default function Plans() {
    return (
        <div>
            <div className="mx-auto max-w-4xl text-center">
                <h1 className="mt-2 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                    Get more with Bref Pro and Enterprise
                </h1>
            </div>
            <p className="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
                Bref is a free and open-source project <a className="link" href="https://github.com/brefphp/bref">hosted on GitHub</a>.
                <br/>
                Bref Pro and Bref Enterprise are support plans offered by <a className="link" href="https://null.tc">Null</a>, the company behind Bref.
            </p>

            {/* xs to lg */}
            <div className="mx-auto mt-12 max-w-md space-y-8 sm:mt-16 lg:hidden">
                {tiers.map((tier) => (
                    <section
                        key={tier.id}
                        className={classNames(
                            tier.mostPopular ? 'rounded-xl bg-gray-400/10 ring-1 ring-inset ring-gray-200' : '',
                            'p-8'
                        )}
                    >
                        <h3 id={tier.id} className="text-sm font-bold leading-6 text-gray-900">
                            {tier.description}
                        </h3>
                        <p className="mt-2 flex items-baseline gap-x-1 text-gray-900">
                            {typeof tier.priceMonthly === 'number' ? (<span className="text-4xl font-bold">{tier.priceMonthly}</span>) : (<span className="text-lg font-bold mt-3">{tier.priceMonthly}</span>)}

                            {typeof tier.priceMonthly === 'number' && (<span className="text-sm font-semibold leading-6">€/month</span>)}
                        </p>
                        <a
                            href={tier.href}
                            aria-describedby={tier.id}
                            className={classNames(
                                tier.mostPopular
                                    ? 'bg-blue-600 text-white hover:bg-blue-500'
                                    : 'text-blue-600 ring-1 ring-inset ring-blue-200 hover:ring-blue-300',
                                'mt-8 block rounded-md py-2 px-3 text-center text-sm font-semibold leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                            )}
                        >
                            {tier.buttonText}
                        </a>
                        <ul role="list" className="mt-10 space-y-4 text-sm leading-6 text-gray-900">
                            {sections.map((section) => (
                                <li key={section.name}>
                                    <ul role="list" className="space-y-4">
                                        {section.features.map((feature) =>
                                            feature.tiers[tier.name] ? (
                                                <li key={feature.name} className="flex gap-x-3">
                                                    <CheckIcon className="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                                                    <span>
                                                        <span dangerouslySetInnerHTML={{ __html: feature.name }} />
                                                        {' '}
                                                        {typeof feature.tiers[tier.name] === 'string' ? (
                                                            <span className="text-sm leading-6 text-gray-500">({feature.tiers[tier.name]})</span>
                                                        ) : null}
                                                    </span>
                                                </li>
                                            ) : null
                                        )}
                                    </ul>
                                </li>
                            ))}
                        </ul>
                    </section>
                ))}
            </div>

            {/* lg+ */}
            <div className="isolate mt-20 hidden lg:block">
                <div className="relative -mx-8">
                    {tiers.some((tier) => tier.mostPopular) ? (
                        <div className="absolute inset-x-4 inset-y-0 -z-10 flex">
                            <div
                                className="flex w-1/4 px-4"
                                aria-hidden="true"
                                style={{ marginLeft: `${(tiers.findIndex((tier) => tier.mostPopular) + 1) * 25}%` }}
                            >
                                <div className="w-full rounded-t-xl border-x border-t border-gray-900/10 bg-gray-400/10" />
                            </div>
                        </div>
                    ) : null}
                    <table className="w-full table-fixed border-separate border-spacing-x-8 text-left">
                        <caption className="sr-only">Pricing plan comparison</caption>
                        <colgroup>
                            <col className="w-1/4" />
                            <col className="w-1/4" />
                            <col className="w-1/4" />
                            <col className="w-1/4" />
                        </colgroup>
                        <thead>
                        <tr>
                            <td />
                            {tiers.map((tier) => (
                                <th key={tier.id} scope="col" className="px-6 pt-6 xl:px-8 xl:pt-8">
                                    <div className="text-sm font-bold leading-7 text-gray-900">{tier.description}</div>
                                </th>
                            ))}
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <th scope="row">
                                <span className="sr-only">Price</span>
                            </th>
                            {tiers.map((tier) => (
                                <td key={tier.id} className="px-6 pt-2 xl:px-8">
                                    <div className="flex items-baseline gap-x-1 text-gray-900 h-8">
                                        {typeof tier.priceMonthly === 'number' ? (<span className="text-4xl font-bold">{tier.priceMonthly}</span>) : (<span className="text-lg font-bold mt-3">{tier.priceMonthly}</span>)}

                                        {typeof tier.priceMonthly === 'number' && (<span className="text-sm font-semibold leading-6">€/month</span>)}
                                    </div>
                                    <a
                                        href={tier.href}
                                        className={classNames(
                                            tier.mostPopular
                                                ? 'bg-blue-600 text-white hover:bg-blue-500'
                                                : 'text-blue-600 ring-1 ring-inset ring-blue-200 hover:ring-blue-300',
                                            'mt-8 block rounded-md py-2 px-3 text-center text-sm font-semibold leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                        )}
                                    >
                                        {tier.buttonText}
                                    </a>
                                </td>
                            ))}
                        </tr>
                        {sections.map((section, sectionIdx) => (
                            <Fragment key={section.name}>
                                <tr>
                                    <th
                                        scope="colgroup"
                                        colSpan={4}
                                        className={classNames(
                                            sectionIdx === 0 ? 'pt-8' : 'pt-16',
                                            'pb-4 text-sm font-semibold leading-6 text-gray-900'
                                        )}
                                    >
                                        {section.name}
                                        <div className="absolute inset-x-8 mt-4 h-px bg-gray-900/10" />
                                    </th>
                                </tr>
                                {section.features.map((feature) => (
                                    <tr key={feature.name}>
                                        <th scope="row" className="py-4 text-sm font-normal leading-6 text-gray-900">
                                            <span dangerouslySetInnerHTML={{ __html: feature.name }} />
                                            <div className="absolute inset-x-8 mt-4 h-px bg-gray-900/5" />
                                        </th>
                                        {tiers.map((tier) => (
                                            <td key={tier.id} className="px-6 py-4 xl:px-8">
                                                {typeof feature.tiers[tier.name] === 'string' ? (
                                                    <div className="text-center text-sm leading-6 text-gray-500">
                                                        {feature.tiers[tier.name]}
                                                    </div>
                                                ) : (
                                                    <>
                                                        {feature.tiers[tier.name] === true ? (
                                                            <CheckIcon className="mx-auto h-5 w-5 text-blue-600" aria-hidden="true" />
                                                        ) : (
                                                            <MinusIcon className="mx-auto h-5 w-5 text-gray-200" aria-hidden="true" />
                                                        )}

                                                        <span className="sr-only">
                                                            {feature.tiers[tier.name] === true ? 'Included' : 'Not included'} in {tier.name}
                                                        </span>
                                                    </>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </Fragment>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    )
}
