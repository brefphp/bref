import { CheckIcon, XMarkIcon } from '@heroicons/react/20/solid'

const tiers = [
    {
        name: 'Bref',
        id: 'bref',
        featured: false,
        description: 'The open-source project.',
        cta: 'Free and open-source',
        ctaLink: null,
    },
    {
        name: 'Bref Cloud',
        id: 'cloud',
        featured: true,
        description: 'The all-in one solution for running your PHP applications.',
        cta: 'View pricing',
        ctaLink: '#pricing',
    },
];
const sections = [
    {
        name: 'Features',
        features: [
            { name: 'Hosting', tiers: { bref: 'AWS Lambda in your AWS account', cloud: 'AWS Lambda in your AWS account' } },
            { name: 'Real-time scaling', tiers: { bref: true, cloud: true }, description: 'AWS Lambda <a class="underline" href="https://bref.sh/docs/how-it-works">scales up/down automatically</a> in a second or less.' },
            { name: 'High-availability', tiers: { bref: true, cloud: true }, description: 'AWS Lambda runs your app redundantly in multiple data centers and automatically replaces instances that fail.' },
            { name: 'PHP runtime for AWS Lambda', tiers: { bref: true, cloud: true }, description: '<a class="underline" href="/docs/vendor-lock-in">Without vendor lock-in</a>' },
            { name: 'Laravel and Symfony integrations', tiers: { bref: true, cloud: true } },
        ],
    },
    {
        name: 'Deployment',
        features: [
            { name: 'Simple serverless.yml configuration', tiers: { bref: true, cloud: true } },
            { name: 'Extensible via custom CloudFormation', tiers: { bref: true, cloud: true } },
            { name: 'Deploy multiple environments (prod, staging…)', tiers: { bref: true, cloud: true } },
            { name: 'Simple deployments from GitHub Actions', tiers: { bref: false, cloud: true }, description: '<a class="underline" href="/docs/cloud-deploy#deploying-from-github-actions">Documentation</a>' },
            { name: 'Simplified creation and management of databases', tiers: { bref: false, cloud: true } },
            { name: 'Simplified creation of private networks', tiers: { bref: false, cloud: 'Coming soon' } },
            { name: 'AWS deployment security', tiers: {
                    bref: {
                        title: 'Your responsibility',
                        description: 'Create a fine-tuned IAM role and use it via access keys (insecure), or SSO roles, or OIDC.',
                    },
                    cloud: {
                        title: 'Automatic',
                        description: 'No direct AWS access needed, no action necessary. Bref Cloud transparently <a class="underline" href="https://bref.sh/docs/cloud-security">creates short-lived access keys</a>.'
                    }
                }
            },
        ],
    },
    {
        name: 'Operations',
        features: [
            { name: 'Applications overview', tiers: {
                    bref: {
                        title: false,
                        description: 'Use the AWS console and switch between AWS services, regions, and AWS accounts.',
                    },
                    cloud: {
                        title: true,
                        description: 'All applications in one place.'
                    }
                }
            },
            { name: 'Logs viewer', tiers: {
                    bref: {
                        title: false,
                        description: 'AWS CloudWatch (advanced)',
                    },
                    cloud: {
                        title: true,
                        description: 'Search logs or view them in real-time.',
                    },
                }
            },
            { name: 'Metrics', tiers: {
                    bref: {
                        title: false,
                        description: 'AWS CloudWatch (advanced)',
                    },
                    cloud: true,
                }
            },
            { name: 'Queue jobs management', tiers: { bref: false, cloud: true } },
            { name: 'Secrets management', tiers: {
                    bref: {
                        title: false,
                        description: 'AWS SSM Parameter Store',
                    },
                    cloud: true,
                }
            },
            { name: 'S3 files management', tiers: {
                    bref: {
                        title: false,
                        description: 'AWS S3 console',
                    },
                    cloud: true,
                }
            },
            { name: 'Healthchecks', tiers: {
                    bref: false,
                    cloud: {
                        title: true,
                        description: 'Laravel-only for now.',
                    },
                }
            },
        ],
    },
    {
        name: 'Security & Access management',
        features: [
            { name: 'Team member permissions', tiers: {
                    bref: {
                        title: 'Your responsibility',
                        description: 'Create IAM roles with fine-tuned permissions (advanced), or use "administrator access" (simple, but very insecure). Then give access to team members via AWS access keys (simple but insecure) or via IAM Identity Center (advanced).',
                    },
                    cloud: {
                        title: true,
                        description: 'Invite teammates to Bref Cloud with read-only, write, or admin access. No AWS access needed.'
                    }
                }
            },
            { name: 'Strong isolation between environments', tiers: {
                    bref: {
                        title: 'Your responsibility',
                        description: 'Create separate AWS accounts (e.g. prod, dev…). Then, set up AWS Organizations and IAM Identity Center so that team members can access them securely via "IAM assume role".',
                    },
                    cloud: {
                        title: true,
                        description: 'Create separate AWS accounts (e.g. prod, dev…). Then access them in one place via Bref Cloud.'
                    }
                }
            },
            { name: 'Audit log', tiers: {
                    bref: {
                        title: false,
                        description: 'AWS CloudTrail (advanced)',
                    },
                    cloud: true,
                }
            },
        ],
    },
    {
        name: 'Support',
        features: [
            { name: 'Priority bugfixes on GitHub', tiers: { bref: false, cloud: true } },
            { name: 'Expert AWS support', tiers: { bref: false, cloud: true } },
        ],
    },
];

function classNames(...classes) {
    return classes.filter(Boolean).join(' ')
}

export default function Features() {
    return (
        <div className="isolate overflow-hidden">
            <div className="relative bg-gray-50 pt-8 lg:pt-14">
                <div className="mx-auto max-w-4xl text-center">
                    <h2 className="text-base/7 font-semibold text-blue-500">Features</h2>
                    <p className="mt-2 text-balance text-4xl sm:text-5xl font-black tracking-tight text-gray-950">
                        Bref <small>vs</small> Bref Cloud
                    </p>
                </div>
                <div className="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                    {/* Feature comparison (up to lg) */}
                    <section aria-labelledby="mobile-comparison-heading" className="lg:hidden">
                        <h2 id="mobile-comparison-heading" className="sr-only">
                            Feature comparison
                        </h2>

                        <div className="mx-auto max-w-2xl space-y-16">

                            {sections.map((section) => (
                                <div key={section.name}>
                                    <h4 className="text-base/6 font-semibold text-gray-900">{section.name}</h4>
                                    <div className="mt-6 text-sm/6">
                                        {section.features.map((feature) => (
                                            <div key={feature.name} className="my-6">
                                                <div className="font-semibold text-gray-600">{feature.name}</div>
                                                {feature.description && (
                                                    <div className="mt-1 text-xs/4 text-gray-500 text-pretty" dangerouslySetInnerHTML={{ __html: feature.description }}></div>
                                                )}
                                                <div className="mt-2 grid grid-cols-2 text-center text-gray-400 text-xs">
                                                    <div>Bref</div>
                                                    <div>Bref Cloud</div>
                                                </div>
                                                <div className="rounded bg-white ring-1 ring-gray-900/10 py-2 grid grid-cols-2 text-center divide-x divide-gray-200">
                                                    {tiers.map((tier) => (
                                                        <div className="px-1.5">
                                                            {(typeof feature.tiers[tier.id] === 'string' || typeof feature.tiers[tier.id]?.title === 'string') && (
                                                                <span
                                                                    className={classNames(
                                                                        tier.featured ? 'font-semibold text-blue-600' : 'text-gray-900',
                                                                        ' text-sm/5',
                                                                    )}
                                                                >
                                                                    {feature.tiers[tier.id]?.title || feature.tiers[tier.id]}
                                                                </span>
                                                            )}
                                                            {(typeof feature.tiers[tier.id] === 'boolean' || typeof feature.tiers[tier.id]?.title === 'boolean') && (
                                                                <div className="flex justify-center">
                                                                    {(feature.tiers[tier.id]?.title || feature.tiers[tier.id]) === true ? (
                                                                        <CheckIcon aria-hidden="true" className="block size-6 text-blue-600" />
                                                                    ) : (
                                                                        <XMarkIcon aria-hidden="true" className="block size-6 text-gray-400" />
                                                                    )}
                                                                    <span className="sr-only">
                                                                        {(feature.tiers[tier.id]?.title || feature.tiers[tier.id]) === true ? 'Yes' : 'No'}
                                                                    </span>
                                                                </div>
                                                            )}
                                                            {typeof feature.tiers[tier.id]?.description === 'string' && (
                                                                <div className="mt-1 text-gray-500 text-xs/4 text-pretty" dangerouslySetInnerHTML={{ __html: feature.tiers[tier.id].description }}></div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Feature comparison (lg+) */}
                    <section aria-labelledby="comparison-heading" className="hidden lg:block">
                        <h2 id="comparison-heading" className="sr-only">
                            Feature comparison
                        </h2>

                        <div className="grid grid-cols-3 gap-x-8 border-t border-gray-900/10 before:block">
                            {tiers.map((tier) => (
                                <div key={tier.id} aria-hidden="true" className="-mt-px">
                                    <div
                                        className={classNames(
                                            tier.featured ? 'border-blue-600' : 'border-transparent',
                                            'border-t-2 pt-10',
                                        )}
                                    >
                                        <p
                                            className={classNames(
                                                tier.featured ? 'text-blue-600' : 'text-gray-900',
                                                'text-sm/6 font-semibold',
                                            )}
                                        >
                                            {tier.name}
                                        </p>
                                        <p className="mt-1 text-sm/6 text-gray-600">{tier.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="-mt-6 space-y-16">
                            {sections.map((section) => (
                                <div key={section.name}>
                                    <h3 className="text-sm/6 font-semibold text-gray-900">{section.name}</h3>
                                    <div className="relative -mx-8 mt-10">
                                        {/* Fake card backgrounds */}
                                        <div
                                            aria-hidden="true"
                                            className="absolute inset-x-8 inset-y-0 grid grid-cols-3 gap-x-8 before:block"
                                        >
                                            <div className="size-full rounded-lg bg-white shadow-sm" />
                                            <div className="size-full rounded-lg bg-white shadow-sm" />
                                        </div>

                                        <table className="relative w-full border-separate border-spacing-x-8">
                                            <thead>
                                            <tr className="text-left">
                                                <th scope="col">
                                                    <span className="sr-only">Feature</span>
                                                </th>
                                                {tiers.map((tier) => (
                                                    <th key={tier.id} scope="col">
                                                        <span className="sr-only">{tier.name} tier</span>
                                                    </th>
                                                ))}
                                            </tr>
                                            </thead>
                                            <tbody>
                                            {section.features.map((feature, featureIdx) => (
                                                <tr key={feature.name} className="relative">
                                                    <th scope="row"
                                                        className="w-1/4 py-3 pr-4 text-left text-sm/6 font-normal text-gray-900">
                                                        <div className="">{feature.name}</div>
                                                        {feature.description && (
                                                            <div
                                                                className="mt-2 text-xs/4 text-gray-500 text-pretty" dangerouslySetInnerHTML={{ __html: feature.description }}></div>
                                                        )}
                                                        {featureIdx !== section.features.length - 1 ? (
                                                            <div
                                                                className="absolute bottom-0 inset-x-0 mt-3 h-px bg-gray-200" />
                                                        ) : null}
                                                    </th>
                                                    {tiers.map((tier) => (
                                                        <td key={tier.id}
                                                            className="relative w-1/4 px-4 py-0 text-center">
                                                            <div className="relative size-full py-3">
                                                                {(typeof feature.tiers[tier.id] === 'string' || typeof feature.tiers[tier.id]?.title === 'string') && (
                                                                    <span
                                                                        className={classNames(
                                                                            tier.featured ? 'font-semibold text-blue-600' : 'text-gray-900',
                                                                            'text-sm/6',
                                                                        )}
                                                                    >
                                                                        {feature.tiers[tier.id]?.title || feature.tiers[tier.id]}
                                                                    </span>
                                                                )}
                                                                {(typeof feature.tiers[tier.id] === 'boolean' || typeof feature.tiers[tier.id]?.title === 'boolean') && (
                                                                    <>
                                                                        {(feature.tiers[tier.id]?.title || feature.tiers[tier.id]) === true ? (
                                                                            <CheckIcon aria-hidden="true"
                                                                                       className="mx-auto size-5 text-blue-600" />
                                                                        ) : (
                                                                            <XMarkIcon aria-hidden="true"
                                                                                       className="mx-auto size-5 text-gray-400" />
                                                                        )}
                                                                        <span className="sr-only">
                                                                            {(feature.tiers[tier.id]?.title || feature.tiers[tier.id]) === true ? 'Yes' : 'No'}
                                                                        </span>
                                                                    </>
                                                                )}
                                                                {typeof feature.tiers[tier.id]?.description === 'string' && (
                                                                    <div className="mt-1 text-gray-500 text-xs/4 text-pretty" dangerouslySetInnerHTML={{ __html: feature.tiers[tier.id].description }}></div>
                                                                )}
                                                            </div>
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                            </tbody>
                                        </table>

                                        {/* Fake card borders */}
                                        <div
                                            aria-hidden="true"
                                            className="pointer-events-none absolute inset-x-8 inset-y-0 grid grid-cols-3 gap-x-8 before:block"
                                        >
                                            {tiers.map((tier) => (
                                                <div
                                                    key={tier.id}
                                                    className={classNames(
                                                        tier.featured ? 'ring-2 ring-blue-600' : 'ring-1 ring-gray-900/10',
                                                        'rounded-lg',
                                                    )}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 grid grid-cols-3 gap-x-8 border-b border-gray-900/10 before:block">
                            {tiers.map((tier) => (
                                <div key={tier.id} aria-hidden="true" className="-mt-px">
                                    <div className="pt-10 pb-2">
                                        <p
                                            className={classNames(
                                                tier.featured ? 'text-blue-600' : 'text-gray-900',
                                                'text-center text-sm/6 font-semibold',
                                            )}
                                        >
                                            {tier.name}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 grid grid-cols-3 gap-x-8 before:block">
                            {tiers.map((tier) => (
                                <div key={tier.id} aria-hidden="true" className="-mt-px">
                                    {tier.ctaLink ? (
                                        <a href={tier.ctaLink}
                                           className="block rounded-md px-3 py-2 text-center text-sm/6 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 bg-blue-500 text-white shadow-sm hover:bg-blue-600 focus-visible:outline-blue-600">{tier.cta}</a>
                                    ) : (
                                        <div
                                            className="block rounded-md px-3 py-2 text-center text-sm/6 font-semibold bg-transparent text-gray-600">{tier.cta}</div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    )
}
