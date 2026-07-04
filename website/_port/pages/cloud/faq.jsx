const faqs = [
    {
        question: 'What is a considered an "application"?',
        answer: 'Technically speaking, an application is a deployment unit, i.e. a CloudFormation stack. In more common terms, an application is a single PHP project. It can for example be a Laravel, Symfony, or any other PHP application. One application can be deployed to multiple environments (dev, staging, production, etc.), but it is still considered a single application. Note that some applications have a JS frontend (like a React app) and a PHP backend: if both are deployed together <a class="link" href="https://bref.sh/docs/use-cases/static-websites">in a single config file</a>, they are considered a single application.',
    },
    {
        question: 'Do I get invoices?',
        answer: 'Yes, you will receive an invoice by email after your purchase.',
    },
    {
        question: 'Can I use AWS credits?',
        answer: 'Yes. Since applications run in your AWS account, you can use your AWS credits for the usage of AWS services.',
    },
    {
        question: 'What\'s your refund policy?',
        answer: 'We generally offer refunds within 14 days of purchase, but please email us to contact@bref.sh if you aren\'t happy with Bref Cloud and we\'ll find a solution.',
    },
    {
        question: 'What are the terms of service and privacy policy?',
        answer: 'Here are the links to the <a class="link" href="https://bref.cloud/terms-of-service">terms of service</a> and the <a class="link" href="https://bref.cloud/privacy-policy">privacy policy</a> of Bref Cloud.',
    },
];

export default function Faq() {
    return (
        <div id="faq" className="bg-white">
            <div className="mx-auto max-w-7xl px-6 py-24 sm:pt-32 lg:px-8 lg:py-40">
                <div className="lg:grid lg:grid-cols-12 lg:gap-8">
                    <div className="lg:col-span-5">
                        <h2 className="text-2xl font-bold leading-10 tracking-tight text-gray-900">
                            Frequently asked questions
                        </h2>
                        <p className="mt-4 text-base leading-7 text-gray-600">
                            Looking for the docs? Check out the <a href="https://bref.sh/docs" className="font-semibold text-blue-600 hover:text-blue-500">Bref Cloud documentation</a>.
                        </p>
                        <p className="mt-2 text-base leading-7 text-gray-600">
                            Can’t find the answer you’re looking for? Reach out <a href="mailto:matthieu@bref.sh" className="font-semibold text-blue-600 hover:text-blue-500">via email</a>.
                        </p>
                    </div>
                    <div className="mt-10 lg:col-span-7 lg:mt-0">
                        <dl className="space-y-10">
                            {faqs.map((faq) => (
                                <div key={faq.question}>
                                    <dt className="text-base font-semibold leading-7 text-gray-900">{faq.question}</dt>
                                    <dd className="mt-2 text-base leading-7 text-gray-600"
                                        dangerouslySetInnerHTML={{ __html: faq.answer }}></dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    )
}
