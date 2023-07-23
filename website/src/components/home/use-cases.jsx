import { CheckIcon } from '@heroicons/react/20/solid';

const features = [
    {
        name: 'Websites',
        description: 'Rerum repellat labore necessitatibus reprehenderit molestiae praesentium.'
    },
    {
        name: 'HTTP APIs',
        description: 'Corporis asperiores ea nulla temporibus asperiores non tempore assumenda aut.'
    },
    {
        name: 'CLI commands',
        description: 'In sit qui aliquid deleniti et. Ad nobis sunt omnis. Quo sapiente dicta laboriosam.'
    },
    {
        name: 'Cron tasks',
        description: 'Sed rerum sunt dignissimos ullam. Iusto iure occaecati voluptate eligendi fugiat sequi.'
    },
    {
        name: 'Job queues',
        description: 'Quos inventore harum enim nesciunt. Aut repellat rerum omnis adipisci.'
    },
    {
        name: 'Event-driven microservices',
        description: 'Eos laudantium repellat sed architecto earum unde incidunt. Illum sit dolores voluptatem.'
    },
    {
        name: 'File processing',
        description: 'Nulla est saepe accusamus nostrum est est. Fugit voluptatum omnis quidem voluptatem.'
    },
    {
        name: 'WebSockets',
        description: 'Nulla est saepe accusamus nostrum est est. Fugit voluptatum omnis quidem voluptatem.'
    },
];

export default function UseCases() {
    return (
        <div className="bg-white py-24 sm:py-32">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    className="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                    <div>
                        <h2 className="mt-2 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">Use cases</h2>
                        <p className="mt-6 text-base leading-7 text-gray-600">
                            Lorem ipsum, dolor sit amet consectetur adipisicing elit. Maiores impedit perferendis
                            suscipit eaque, iste
                            dolor cupiditate blanditiis ratione.
                        </p>
                    </div>
                    <dl className="col-span-2 grid grid-cols-1 gap-x-8 gap-y-10 text-base leading-7 text-gray-600 sm:grid-cols-2 lg:gap-y-16">
                        {features.map((feature) => (
                            <div key={feature.name} className="relative pl-9">
                                <dt className="font-semibold text-gray-900">
                                    <CheckIcon className="absolute left-0 top-1 h-5 w-5 text-blue-500"
                                               aria-hidden="true" />
                                    {feature.name}
                                </dt>
                                <dd className="mt-2">{feature.description}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </div>
    );
}
