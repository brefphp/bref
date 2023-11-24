import { CheckIcon } from '@heroicons/react/20/solid';

const simpleUseCases = [
    {
        name: 'Websites',
        description: 'Run PHP websites with Laravel, Symfony or any other framework, with a worldwide CDN and your custom domain.'
    },
    {
        name: 'HTTP APIs',
        description: 'REST or GraphQL APIs deployed in seconds. Need more performance? Enable <strong>Laravel Octane</strong> or the Symfony equivalent.'
    },
    {
        name: 'CLI commands',
        description: 'Run DB migrations, admin commands, or any other CLI command from your machine or your CI/CD.'
    },
    {
        name: 'Cron tasks',
        description: 'Every day, every hour, every minute… Run CLI scripts, Symfony Console commands, or the Laravel Scheduler.'
    },
];

const advancedUseCases = [
    {
        name: 'Job queues',
        description: 'Run 1000 jobs with 1 worker in 1000 seconds, or with <strong>1000 workers</strong> in 1 second. It\'s just as simple and it costs the same. SQS invokes your code directly, no long-running process to maintain.'
    },
    {
        name: 'Event-driven microservices',
        description: 'Decouple and scale microservices without container madness. Send messages to EventBridge and let it invoke your PHP classes directly. No integration to write.'
    },
    {
        name: 'File processing',
        description: 'S3 can invoke a PHP class whenever a new file is uploaded. Resize images, convert videos, generate PDFs…'
    },
    {
        name: 'WebSockets',
        description: 'AWS API Gateway manages the WebSocket connections for you. Send messages to your users in real-time.'
    },
];

export default function UseCases() {
    return (
        <div className="home-container home-section">
            <div className="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <div>
                    <h2 className="mt-2 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">Use cases</h2>
                    <p className="mt-6 text-base leading-7 text-gray-600">
                        Serverless means whatever you choose it to mean.
                    </p>
                    <p className="mt-6 text-base leading-7 text-gray-600">
                        Run PHP as usual, {' '}<strong>like on any server</strong>.
                        Except it scales (almost) infinitely and you don't maintain the infrastructure.<br/>
                        Lift-and-shift existing apps or build new ones with your favorite framework.
                    </p>
                </div>
                <dl className="col-span-2 grid grid-cols-1 gap-x-8 gap-y-10 text-base leading-7 text-gray-600 sm:grid-cols-2 lg:gap-y-16">
                    {simpleUseCases.map((feature) => (
                        <div key={feature.name} className="relative pl-9">
                            <dt className="font-semibold text-gray-900">
                                <CheckIcon className="absolute left-0 top-1 h-5 w-5 text-blue-500"
                                           aria-hidden="true" />
                                {feature.name}
                            </dt>
                            <dd className="mt-2" dangerouslySetInnerHTML={{ __html: feature.description }}></dd>
                        </div>
                    ))}
                </dl>
            </div>
            <div className="mt-16 sm:mt-20 mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <div>
                    <p className="text-base leading-7 text-gray-600">
                        Or go the extreme opposite: build {' '}<strong>event-driven microservices</strong> with infinitely scalable cloud services like SQS and EventBridge.
                    </p>
                    <p className="mt-6 text-base leading-7 text-gray-600">
                        Or anything in between, that works too.
                    </p>
                </div>
                <dl className="col-span-2 grid grid-cols-1 gap-x-8 gap-y-10 text-base leading-7 text-gray-600 sm:grid-cols-2 lg:gap-y-16">
                    {advancedUseCases.map((feature) => (
                        <div key={feature.name} className="relative pl-9">
                            <dt className="font-semibold text-gray-900">
                                <CheckIcon className="absolute left-0 top-1 h-5 w-5 text-blue-500"
                                           aria-hidden="true" />
                                {feature.name}
                            </dt>
                            <dd className="mt-2" dangerouslySetInnerHTML={{ __html: feature.description }}></dd>
                        </div>
                    ))}
                </dl>
            </div>
        </div>
    );
}
