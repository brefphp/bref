import { CheckIcon } from '@heroicons/react/20/solid';

const benefits = [
    ['Included in Bref v4.', 'Your extension ships in the default runtime. Users enable it with one line.'],
    ['Officially maintained by Bref.', 'Every PHP version, x86 and ARM, rebuilt with every release.'],
    ['A documentation page.', '"Using ${your product} with Bref", on bref.sh, written with you.'],
    ['Your logo.', 'On the extensions page, in the docs, and on the home page.'],
    ['A launch announcement.', 'In the Bref newsletter and the "News" section.'],
    ['Bref v4 release sponsor.', 'If you sign up before the v4 release.'],
];

const comparison = [
    ['Where', 'Separate package', 'In the default runtime'],
    ['To enable', 'Install and wire it per project', 'One line in php.ini'],
    ['Maintenance', 'Community, best effort', 'Bref, continuously updated'],
    ['Docs', 'A 3rd party README', 'A page on bref.sh'],
];

const faqs = [
    {
        question: 'Which PHP versions and architectures are covered?',
        answer: 'Every PHP version supported by Bref v4, on x86 and ARM. New PHP versions are covered when they ship.',
    },
    {
        question: 'Do users need a paid Bref plan?',
        answer: 'No. The extension is in the open-source runtime: every Bref user can enable it.',
    },
    {
        question: 'Can a competitor sponsor their extension too?',
        answer: 'Yes, on the same terms. There is no exclusivity.',
    },
    {
        question: 'We don\'t ship a PHP extension. Can we sponsor?',
        answer: 'Yes. Databases and other services Bref users may use can sponsor Bref too. Get in touch and we\'ll figure it out.',
    },
    {
        question: 'How do we pay?',
        answer: <>
            By card or bank transfer on invoice, or through <a href="https://github.com/sponsors/mnapoli" className="font-semibold text-blue-600 underline hover:text-blue-800">GitHub Sponsors</a>.
            We're happy to go through your supplier onboarding.
        </>,
    },
    {
        question: 'What happens if we stop sponsoring?',
        answer: 'The extension is no longer maintained. It stays in the runtime as long as it builds, with no guarantee, and may be removed if it breaks a Bref release.',
    },
];

function InlineCode({ children }) {
    return <code className="font-mono text-sm font-semibold text-gray-900">{children}</code>;
}

function DocsMock() {
    return (
        <div className="mx-auto mt-14 max-w-5xl rounded-t-2xl bg-gray-900/5 px-3 pt-3 text-left ring-1 ring-inset ring-gray-900/10">
            <div className="overflow-hidden rounded-t-xl bg-white shadow-2xl">
                <div className="flex items-center gap-2 border-b border-gray-200 px-4 py-2.5">
                    <span className="size-2.5 rounded-full bg-gray-200" />
                    <span className="size-2.5 rounded-full bg-gray-200" />
                    <span className="size-2.5 rounded-full bg-gray-200" />
                    <span className="ml-3 truncate font-mono text-xs text-gray-500">bref.sh/docs/your-product</span>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-[220px_minmax(0,1fr)]">
                    <div className="hidden border-r border-gray-200 px-4 py-6 text-[13px] leading-7 text-gray-600 md:block">
                        <div className="font-semibold text-gray-900">Integrations</div>
                        <div>Sentry</div>
                        <div>X-Ray</div>
                        <div className="-mx-2 my-0.5 rounded-md bg-blue-100 px-2 font-semibold text-blue-800">Your product</div>
                        <div>Redis</div>
                        <div>PlanetScale</div>
                    </div>
                    <div className="min-w-0 px-8 pt-8 pb-10">
                        <div className="flex flex-wrap items-center gap-4">
                            <span className="inline-flex h-10 items-center justify-center rounded-md bg-gray-400/10 px-4 text-[13px] font-bold text-gray-700">YOUR LOGO</span>
                            <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">Vendor sponsor</span>
                        </div>
                        <div className="mt-5 text-3xl font-bold tracking-tight text-gray-900">Using {'${your product}'} with Bref</div>
                        <p className="mt-3 max-w-xl text-base leading-7 text-gray-600">
                            The <InlineCode>your_extension</InlineCode> extension is included in Bref. Enable it in <InlineCode>php/conf.d/php.ini</InlineCode>:
                        </p>
                        <pre className="mt-4 overflow-x-auto rounded-xl bg-gray-900 px-5 py-4 font-mono text-sm text-gray-200">
                            extension=<span className="text-blue-400">your_extension</span>
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function VendorSponsorship() {
    return (
        <div className="text-gray-600">

            {/* Hero */}
            <div className="relative isolate overflow-hidden bg-white">
                <svg aria-hidden="true" className="absolute inset-0 -z-10 size-full stroke-gray-200">
                    <defs>
                        <pattern id="vendor-sponsorship-grid" x="50%" y={-1} width={200} height={200} patternUnits="userSpaceOnUse">
                            <path d="M.5 200V.5H200" fill="none" />
                        </pattern>
                    </defs>
                    <rect fill="url(#vendor-sponsorship-grid)" width="100%" height="100%" strokeWidth={0} />
                </svg>
                <div className="mx-auto max-w-7xl px-6 pt-12 text-center sm:pt-20 lg:px-8">
                    <div className="mx-auto max-w-3xl">
                        <h1 className="text-balance text-4xl font-black leading-tight tracking-tight text-gray-900 sm:text-5xl">
                            Vendor sponsorship
                        </h1>
                        <p className="mx-auto mt-7 max-w-2xl text-lg leading-8">
                            Bref serves <strong className="font-semibold text-gray-900">30 billion</strong> PHP requests a month on AWS Lambda,
                            and is installed <a href="https://packagist.org/packages/bref/bref/stats" className="font-semibold text-gray-900 underline decoration-gray-300 underline-offset-[3px]">500,000 times</a> a month.
                        </p>
                        <p className="mx-auto mt-4 max-w-150 text-lg leading-8">
                            Sponsor Bref to make your PHP extension available <strong className="font-semibold text-gray-900">out of the box</strong> to all Bref users.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-6">
                            <a
                                href="#contact"
                                className="rounded-md bg-blue-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            >
                                Become a vendor sponsor
                            </a>
                            <a href="#pricing" className="text-sm/6 font-semibold text-gray-900">
                                Pricing <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                    <DocsMock />
                </div>
            </div>

            {/* What vendor sponsors get */}
            <div className="border-y border-gray-200 bg-gray-50">
                <div className="mx-auto max-w-7xl px-6 py-20 sm:py-28 lg:px-8">
                    <h2 className="text-balance text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                        What vendor sponsors get
                    </h2>
                    <ul role="list" className="mt-10 grid grid-cols-1 gap-x-12 gap-y-8 text-base leading-7 md:grid-cols-2">
                        {benefits.map(([title, description]) => (
                            <li key={title} className="flex gap-3">
                                <CheckIcon aria-hidden="true" className="h-7 w-5 flex-none text-blue-600" />
                                <span><strong className="font-semibold text-gray-900">{title}</strong> {description}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            {/* How it works */}
            <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-6 py-20 sm:py-28 lg:grid-cols-[5fr_7fr] lg:px-8">
                <div>
                    <h2 className="text-balance text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                        How it works
                    </h2>
                    <p className="mt-6 text-lg leading-8">
                        Bref runtimes ship a set of extensions by default. Other extensions are community-maintained, published separately, with no guarantee.
                    </p>
                    <p className="mt-4 text-base leading-7">
                        A sponsored extension moves into the default runtime. We build and maintain it, document it on bref.sh,
                        and your users turn it on in <InlineCode>php.ini</InlineCode>.
                    </p>
                </div>
                <div className="self-start overflow-hidden rounded-2xl text-sm leading-6 ring-1 ring-inset ring-gray-900/10">
                    <div className="hidden grid-cols-[0.8fr_1fr_1fr] gap-4 bg-gray-50 px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 sm:grid">
                        <span />
                        <span>Community extension</span>
                        <span className="text-blue-700">Sponsored extension</span>
                    </div>
                    {comparison.map(([label, community, sponsored], index) => (
                        <div
                            key={label}
                            className={`grid grid-cols-1 gap-0.5 px-4 py-3.5 sm:grid-cols-[0.8fr_1fr_1fr] sm:gap-4 sm:px-6 sm:py-4 ${index > 0 ? 'border-t border-gray-200' : ''}`}
                        >
                            <span className="font-semibold text-gray-900 sm:font-normal sm:text-gray-500">{label}</span>
                            <span>{community}</span>
                            <span className="font-medium text-gray-900">{sponsored}</span>
                        </div>
                    ))}
                </div>
            </div>

            {/* Pricing */}
            <div id="pricing" className="scroll-mt-16 border-t border-gray-200">
                <div className="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-20 sm:py-28 lg:grid-cols-[5fr_7fr] lg:px-8">
                    <div>
                        <h2 className="text-balance text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                            Pricing
                        </h2>
                        <p className="mt-4 text-base leading-7">
                            Sponsoring gets you inclusion, maintenance and visibility.
                            The docs stay neutral, and any vendor can sponsor their own extension on the same terms.
                        </p>
                    </div>
                    <div className="w-full max-w-120 justify-self-center rounded-3xl bg-white p-8 shadow-2xl ring-1 ring-gray-900/10 sm:p-10">
                        <h3 className="text-base/7 font-semibold text-blue-600">Vendor sponsor</h3>
                        <div className="mt-4 flex flex-wrap items-baseline gap-x-2">
                            <span className="text-5xl font-bold tracking-tight text-gray-900">4,000€</span>
                            <span className="text-xs text-gray-500">per extension, per year</span>
                        </div>
                        <a
                            href="#contact"
                            className="mt-8 block w-full rounded-md bg-blue-500 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            Become a vendor sponsor
                        </a>
                        <p className="mt-4 text-center text-xs text-gray-500">
                            Pay yearly by invoice, or through <a href="https://github.com/sponsors/mnapoli" className="text-gray-600 underline underline-offset-[3px]">GitHub Sponsors</a>.
                        </p>
                    </div>
                </div>
            </div>

            {/* FAQ */}
            <div className="border-t border-gray-200">
                <div className="mx-auto max-w-7xl px-6 py-20 sm:py-28 lg:px-8">
                    <div className="lg:grid lg:grid-cols-12 lg:gap-8">
                        <div className="lg:col-span-5">
                            <h2 className="text-2xl/10 font-bold tracking-tight text-gray-900">Frequently asked questions</h2>
                            <p className="mt-4 text-base leading-7">
                                Something else? Write to <a href="mailto:matthieu@bref.sh" className="font-semibold text-blue-600 underline hover:text-blue-800">matthieu@bref.sh</a>.
                            </p>
                        </div>
                        <dl className="mt-10 space-y-10 lg:col-span-7 lg:mt-0">
                            {faqs.map((faq) => (
                                <div key={faq.question}>
                                    <dt className="text-base/7 font-semibold text-gray-900">{faq.question}</dt>
                                    <dd className="mt-2 text-base/7">{faq.answer}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
            </div>

            {/* Call to action */}
            <div id="contact" className="scroll-mt-16 pb-24">
                <div className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="relative isolate flex flex-wrap items-center justify-between gap-8 overflow-hidden rounded-3xl bg-blue-500 px-6 py-10 sm:px-12 sm:py-14">
                        <div
                            aria-hidden="true"
                            className="absolute -inset-y-px left-[55%] -z-10 w-[200%] skew-x-[-30deg] bg-blue-100 opacity-20"
                        />
                        <div className="max-w-155 text-white">
                            <h2 className="text-balance text-3xl font-black tracking-tight sm:text-4xl">
                                Sponsor before the Bref v4 release
                            </h2>
                            <p className="mt-5 text-lg leading-7">
                                Your extension is in the runtime on day one and you appear as a Bref v4 release&nbsp;sponsor.
                            </p>
                            <p className="mt-4 text-sm">
                                Bref v4 targets December 2026.
                            </p>
                        </div>
                        <a
                            href="mailto:matthieu@bref.sh?subject=Vendor%20sponsor"
                            className="inline-flex items-center rounded-md bg-white px-4.5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                        >
                            Talk to Matthieu <span aria-hidden="true" className="ml-1.5">→</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    );
}
