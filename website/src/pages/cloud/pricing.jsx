import { useState } from 'react'
import { Radio, RadioGroup } from '@headlessui/react'
import { CheckIcon } from '@heroicons/react/20/solid'

const frequencies = [
    { value: 'monthly', label: 'Monthly', priceSuffix: '/month' },
    { value: 'annually', label: 'Annually', priceSuffix: '/year' },
]
const tiers = [
    {
        name: 'Personal',
        id: 'tier-free',
        href: 'https://bref.cloud/register',
        price: 'Free',
        description: 'Deploy and host your personal projects.',
        features: [
            '1 user',
            '2 applications',
            '1 AWS account',
            'Unlimited deployments',
            'Unlimited environments',
            'Non-commercial applications only',
        ],
        featured: false,
        cta: 'Get started',
    },
    {
        name: 'Startup',
        id: 'tier-startup',
        href: 'https://bref.cloud/register',
        price: { monthly: '$39', annually: '$399' },
        description: 'The essentials for small dev teams that want to grow.',
        features: [
            '5 users',
            '5 applications',
            '3 AWS accounts',
            'Unlimited deployments',
            'Unlimited environments',
            'Slack + email support & consulting',
        ],
        featured: false,
        cta: 'Get started',
    },
    {
        name: 'Team',
        id: 'tier-team',
        href: 'https://bref.cloud/register',
        price: { monthly: '$99', annually: '$999' },
        description: 'A plan for businesses with multiple dev teams.',
        features: [
            '15 users',
            '50 applications',
            'Unlimited AWS accounts',
            'Unlimited deployments',
            'Unlimited environments',
            'Slack + email support & consulting',
        ],
        featured: false,
        cta: 'Get started',
    },
    {
        name: 'Enterprise',
        id: 'tier-enterprise',
        href: 'https://calendly.com/bref-enterprise/intro',
        price: 'Custom',
        description: 'Dedicated support and infrastructure for your company.',
        features: [
            'Unlimited users',
            'Unlimited applications',
            'Unlimited AWS accounts',
            'Unlimited deployments',
            'Unlimited environments',
            'Priority support & consulting',
            'Zoom consulting',
            'Tailored AWS infrastructure',
            'Self-hosted Bref Cloud',
            'Access to Bref Cloud source code',
        ],
        featured: true,
        cta: 'Get in touch',
    },
]

function classNames(...classes) {
    return classes.filter(Boolean).join(' ')
}

export default function Pricing() {
    const [frequency, setFrequency] = useState(frequencies[0])

    return (
        <div id="pricing" className="bg-white py-24 sm:py-32">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="mx-auto max-w-4xl text-center">
                    <h2 className="text-base/7 font-semibold text-blue-500">Pricing</h2>
                    <p className="mt-2 text-balance text-5xl font-semibold tracking-tight text-gray-900 sm:text-6xl">
                        A plan for every team
                    </p>
                </div>
                <div className="mt-12 flex justify-center">
                    <fieldset aria-label="Payment frequency">
                        <RadioGroup
                            value={frequency}
                            onChange={setFrequency}
                            className="grid grid-cols-2 gap-x-1 rounded-full p-1 text-center text-xs/5 font-semibold ring-1 ring-inset ring-gray-200"
                        >
                            {frequencies.map((option) => (
                                <Radio
                                    key={option.value}
                                    value={option}
                                    className="cursor-pointer rounded-full px-2.5 py-1 text-gray-500 data-[checked]:bg-blue-500 data-[checked]:text-white"
                                >
                                    {option.label}
                                </Radio>
                            ))}
                        </RadioGroup>
                    </fieldset>
                </div>
                <div
                    className="isolate mx-auto mt-10 grid max-w-md grid-cols-1 gap-6 lg:mx-0 lg:max-w-none lg:grid-cols-4">
                    {tiers.map((tier) => (
                        <div
                            key={tier.id}
                            className={classNames(
                                tier.featured ? 'bg-gray-900 ring-gray-900' : 'ring-gray-200',
                                'rounded-3xl p-8 ring-1',
                            )}
                        >
                            <h3
                                id={tier.id}
                                className={classNames(tier.featured ? 'text-white' : 'text-gray-900', 'text-lg/8 font-semibold')}
                            >
                                {tier.name}
                            </h3>
                            <p className={classNames(tier.featured ? 'text-gray-300' : 'text-gray-600', 'mt-4 text-sm/6')}>
                                {tier.description}
                            </p>
                            <p className="mt-6 flex items-baseline gap-x-1">
                                <span
                                    className={classNames(
                                        tier.featured ? 'text-white' : 'text-gray-900',
                                        'text-4xl font-semibold tracking-tight',
                                    )}
                                >
                                    {typeof tier.price === 'string' ? tier.price : tier.price[frequency.value]}
                                </span>
                                {typeof tier.price !== 'string' ? (
                                    <span
                                        className={classNames(tier.featured ? 'text-gray-300' : 'text-gray-600', 'text-sm/6 font-semibold')}
                                    >
                                        {frequency.priceSuffix}
                                    </span>
                                ) : null}
                            </p>
                            <p className={classNames(tier.featured ? 'text-gray-500' : 'text-gray-400', 'mt-2 text-xs leading-3')}>
                                AWS cloud costs are not included
                            </p>
                            <a
                                href={tier.href}
                                aria-describedby={tier.id}
                                className={classNames(
                                    tier.featured
                                        ? 'bg-white/10 text-white hover:bg-white/20 focus-visible:outline-white'
                                        : 'bg-blue-500 text-white shadow-sm hover:bg-blue-600 focus-visible:outline-blue-600',
                                    'mt-6 block rounded-md px-3 py-2 text-center text-sm/6 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                                )}
                            >
                                {tier.cta}
                            </a>
                            <ul
                                role="list"
                                className={classNames(
                                    tier.featured ? 'text-gray-300' : 'text-gray-600',
                                    'mt-8 space-y-3 text-sm/6 xl:mt-10',
                                )}
                            >
                                {tier.features.map((feature) => (
                                    <li key={feature} className="flex gap-x-3">
                                        <CheckIcon
                                            aria-hidden="true"
                                            className={classNames(tier.featured ? 'text-white' : 'text-blue-500', 'h-6 w-5 flex-none')}
                                        />
                                        {feature}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div
                    className="mt-6 flex flex-col items-start gap-x-8 gap-y-6 rounded-3xl p-8 ring-1 ring-gray-900/10 sm:gap-y-10 lg:col-span-2 lg:flex-row lg:items-center">
                    <div className="lg:min-w-0 lg:flex-1">
                        <h3 className="text-base/7 font-semibold text-blue-500">Indie maker</h3>
                        <p className="mt-1 text-base/7 text-gray-600">
                            Working solo on your projects? Get a discounted price that allows commercial projects on the "Personal" plan.
                        </p>
                    </div>
                    <a
                        href="https://bref.cloud/register"
                        className="rounded-md px-3.5 py-2 text-sm/6 font-semibold text-blue-600 ring-1 ring-inset ring-blue-200 hover:ring-blue-500 hover:bg-blue-500 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    >
                        Get started at $15/month
                    </a>
                </div>

                <p className="mt-6 text-xs text-gray-500 text-center">
                    All prices are excluding taxes.
                    You can remove VAT on checkout by adding your VAT ID.
                </p>

            </div>
        </div>
    )
}
