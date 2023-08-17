import { CheckCircleIcon, InformationCircleIcon } from '@heroicons/react/20/solid'

export default function Example() {
    const date = new Date();
    const year = date.getFullYear();

    return (
        <div className="home-container home-section max-w-3xl text-base leading-7 text-gray-700">
            <h2 className="mt-2 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                Why Bref? Why serverless?
            </h2>
            <p className="mt-6 text-xl leading-8 max-w-3xl">
                We're in {year}.
                Applications should <strong>scale</strong> automatically.
                Hosting should be <strong>reliable</strong> and <strong>cost-efficient</strong>.
                Infrastructure should accelerate development, not consume our time.
            </p>
            <div className="mt-8 max-w-3xl">
                <p>
                    Faucibus commodo massa rhoncus, volutpat. Dignissim sed eget risus enim. Mattis mauris semper sed amet vitae
                    sed turpis id. Id dolor praesent donec est. Odio penatibus risus viverra tellus varius sit neque erat velit.
                    Faucibus commodo massa rhoncus, volutpat. Dignissim sed eget risus enim. Mattis mauris semper sed amet vitae
                    sed turpis id.
                </p>
                <ul role="list" className="mt-8 max-w-xl space-y-8 text-gray-600">
                    <li className="flex gap-x-3">
                        <CheckCircleIcon className="mt-1 h-5 w-5 flex-none text-blue-500" aria-hidden="true" />
                        <span>
                            <strong className="font-semibold text-gray-900">Simpler.</strong> Lorem ipsum, dolor sit amet
                            consectetur adipisicing elit. Maiores impedit perferendis suscipit eaque, iste dolor cupiditate
                            blanditiis ratione.
                        </span>
                    </li>
                    <li className="flex gap-x-3">
                        <CheckCircleIcon className="mt-1 h-5 w-5 flex-none text-blue-500" aria-hidden="true" />
                        <span>
                            <strong className="font-semibold text-gray-900">Scalable.</strong> Anim aute id magna aliqua ad ad non
                            deserunt sunt. Qui irure qui lorem cupidatat commodo.
                        </span>
                    </li>
                    <li className="flex gap-x-3">
                        <CheckCircleIcon className="mt-1 h-5 w-5 flex-none text-blue-500" aria-hidden="true" />
                        <span>
                            <strong className="font-semibold text-gray-900">Cost efficient.</strong> Ac tincidunt sapien vehicula erat
                            auctor pellentesque rhoncus. Et magna sit morbi lobortis.
                        </span>
                    </li>
                </ul>
                <p className="mt-8">
                    Serverless provides more scalable, affordable, and reliable architectures for less effort.
                </p>
            </div>
        </div>
    )
}
