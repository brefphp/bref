'use client';

import { BugAntIcon, ClockIcon, CodeBracketSquareIcon, FingerPrintIcon, MagnifyingGlassIcon } from '@heroicons/react/20/solid';
import sentry from './sentry.png';
import Image from 'next/image';
import Breadcrumbs from '../Breadcrumbs';

export default function SentryPage() {
    const features = [
        {
            name: 'Advanced error tracking.',
            description: 'Track AWS Lambda errors that are outside of PHP-FPM, like timeouts or "response too big" errors in Sentry.',
            icon: MagnifyingGlassIcon,
        },
        {
            name: 'Non-HTTP exception tracking.',
            description: 'Add exception tracking to AWS Lambda handlers like SQS, EventBridge, S3 handlers, and more.',
            icon: BugAntIcon,
        },
        {
            name: 'Cold start tracking.',
            description: 'Track cold starts and Bref startup duration in Sentry performance tracing.',
            icon: ClockIcon,
        },
        {
            name: 'Lambda handler performance tracking.',
            description: 'Trace the performance of event-driven PHP Lambda handlers (SQS, EventBridge, etc.).',
            icon: CodeBracketSquareIcon,
        },
        {
            name: 'AWS SDK tracking.',
            description: 'Measure AWS SDK calls in performance tracing to troubleshoot performance issues.',
            icon: FingerPrintIcon,
        },
    ];

    const faqs = [
        {
            question: 'Where can I learn more about each feature?',
            answer: 'You can find more information about each feature in <a href="/sentry/docs" class="link">the documentation</a>.',
        },
        {
            question: 'What are the terms of service?',
            answer: 'Please follow the following link to read the <a href="/sentry/terms" class="link">Terms of service</a> for the Sentry integration.',
        },
    ];

    return (
        <div>
            <div className="py-8 pb-12">
                <div className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl sm:text-center">
                        <Breadcrumbs pages={[
                            { name: 'Sentry', href: '/sentry' },
                        ]} />
                        <h1 className="mt-20 sm:mt-24 text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                            Sentry integration for AWS Lambda
                        </h1>
                        <div className="mt-6 text-lg leading-8 text-gray-600">
                            Advanced Sentry integration for PHP applications running on AWS Lambda. Track cold starts, event-driven invocations, and more.
                        </div>
                        <div className="mt-10 flex items-center justify-center gap-x-6">
                            <a
                                href="/sentry/docs"
                                className="rounded-md bg-blue-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
                            >
                                Documentation
                            </a>
                            <a href="#access" className="text-sm font-semibold leading-6 text-gray-900">
                                Get access <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div className="relative overflow-hidden pt-16">
                    <div className="mx-auto max-w-7xl px-6 lg:px-8">
                        <Image
                            src={sentry}
                            alt="Sentry integration for AWS Lambda"
                            className="mb-[-12%] rounded-xl shadow-2xl ring-1 ring-gray-900/10"
                        />
                        <div className="relative" aria-hidden="true">
                            <div className="absolute -inset-x-20 bottom-0 bg-gradient-to-t from-white pt-[7%]" />
                        </div>
                    </div>
                </div>
                <div className="mx-auto mt-16 max-w-7xl px-6 sm:mt-20 md:mt-24 lg:px-8">
                    <dl className="mx-auto grid max-w-2xl grid-cols-1 gap-x-6 gap-y-10 text-base leading-7 text-gray-600 sm:grid-cols-2 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:gap-x-8 lg:gap-y-16">
                        {features.map((feature) => (
                            <div key={feature.name} className="relative pl-9">
                                <dt className="inline font-semibold text-gray-900">
                                    <feature.icon className="absolute left-1 top-1 h-5 w-5 text-blue-500"
                                                  aria-hidden="true" />
                                    {feature.name}
                                </dt>
                                {' '}
                                <dd className="inline">{feature.description}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>

            <div id="access" className="relative isolate bg-white px-6 py-24 sm:py-32 lg:px-8">
                <div className="mx-auto max-w-2xl text-center">
                    <div className="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        Changes are coming
                    </div>
                    <div className="mt-6 text-lg leading-8 text-gray-600">
                        We are making changes to the Sentry integration, and new licenses are not available for purchase at the moment.
                        To get access to the Sentry integration, <a href="mailto:matthieu@bref.sh?subject=Sentry%20integration" className="font-semibold text-blue-600 hover:text-blue-500">contact matthieu@bref.sh</a>.
                    </div>
                </div>
            </div>

            <div id="faq" className="bg-white">
                <div className="mx-auto max-w-7xl px-6 py-24 sm:pt-32 lg:px-8 lg:py-40">
                    <div className="lg:grid lg:grid-cols-12 lg:gap-8">
                        <div className="lg:col-span-5">
                            <h2 className="text-2xl font-bold leading-10 tracking-tight text-gray-900">Frequently asked questions</h2>
                            <div className="mt-4 text-base leading-7 text-gray-600">
                                Can’t find the answer you’re looking for? Reach out <a href="mailto:matthieu@bref.sh" className="font-semibold text-blue-600 hover:text-blue-500">via email</a>.
                            </div>
                        </div>
                        <div className="mt-10 lg:col-span-7 lg:mt-0">
                            <dl className="space-y-10">
                                {faqs.map((faq) => (
                                    <div key={faq.question}>
                                        <dt className="text-base font-semibold leading-7 text-gray-900">{faq.question}</dt>
                                        <dd className="mt-2 text-base leading-7 text-gray-600" dangerouslySetInnerHTML={{ __html: faq.answer }}></dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
