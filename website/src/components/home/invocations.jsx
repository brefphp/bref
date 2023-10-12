export default function Invocations({ invocations }) {
    return (
        <div className="home-container home-section !px-0 sm:!px-6 !py-12 sm:!py-16">
            <div
                className="relative isolate overflow-hidden bg-gray-900 px-6 py-16 text-center shadow-2xl sm:rounded-3xl sm:px-16">
                <h2 className="mx-auto max-w-2xl text-3xl font-black tracking-tight text-white sm:text-5xl">
                    {invocations?.toLocaleString('en-US')}
                </h2>
                <p className="mx-auto mt-2 max-w-xl text-lg leading-8 text-gray-300">
                    requests, jobs, and messages handled with Bref in the <strong>last 30 days</strong>
                </p>
                {/*<div className="mt-4 flex items-center justify-center gap-x-6">*/}
                {/*    <a href="#" className="text-sm font-semibold leading-6 text-white">*/}
                {/*        Learn more <span aria-hidden="true">→</span>*/}
                {/*    </a>*/}
                {/*</div>*/}
                <form action="https://app.convertkit.com/forms/5696241/subscriptions"
                      className="w-full flex flex-col items-center justify-center mt-10"
                      method="post" data-sv-form="5696241" data-uid="ee838f35c4" data-format="inline" data-version="5"
                      data-options="{&quot;settings&quot;:{&quot;after_subscribe&quot;:{&quot;action&quot;:&quot;message&quot;,&quot;success_message&quot;:&quot;Success! Now check your email to confirm your subscription.&quot;,&quot;redirect_url&quot;:&quot;&quot;},&quot;analytics&quot;:{&quot;google&quot;:null,&quot;fathom&quot;:null,&quot;facebook&quot;:null,&quot;segment&quot;:null,&quot;pinterest&quot;:null,&quot;sparkloop&quot;:null,&quot;googletagmanager&quot;:null},&quot;modal&quot;:{&quot;trigger&quot;:&quot;timer&quot;,&quot;scroll_percentage&quot;:null,&quot;timer&quot;:5,&quot;devices&quot;:&quot;all&quot;,&quot;show_once_every&quot;:15},&quot;powered_by&quot;:{&quot;show&quot;:true,&quot;url&quot;:&quot;https://convertkit.com/features/forms?utm_campaign=poweredby&amp;utm_content=form&amp;utm_medium=referral&amp;utm_source=dynamic&quot;},&quot;recaptcha&quot;:{&quot;enabled&quot;:false},&quot;return_visitor&quot;:{&quot;action&quot;:&quot;show&quot;,&quot;custom_content&quot;:&quot;&quot;},&quot;slide_in&quot;:{&quot;display_in&quot;:&quot;bottom_right&quot;,&quot;trigger&quot;:&quot;timer&quot;,&quot;scroll_percentage&quot;:null,&quot;timer&quot;:5,&quot;devices&quot;:&quot;all&quot;,&quot;show_once_every&quot;:15},&quot;sticky_bar&quot;:{&quot;display_in&quot;:&quot;top&quot;,&quot;trigger&quot;:&quot;timer&quot;,&quot;scroll_percentage&quot;:null,&quot;timer&quot;:5,&quot;devices&quot;:&quot;all&quot;,&quot;show_once_every&quot;:15}},&quot;version&quot;:&quot;5&quot;}">
                    <ul className="text-red-400 font-bold" data-element="errors" data-group="alert"></ul>
                    <div data-element="fields" data-stacked="false"
                         className="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                        <input
                            className="min-w-0 flex-auto rounded-md border-0 bg-white/10 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 placeholder:text-white/75 focus:ring-2 focus:ring-inset focus:ring-white sm:text-sm sm:leading-6"
                            name="email_address" aria-label="Email" placeholder="you@example.com" required type="email" />
                        <button data-element="submit" type="submit"
                                className="flex-none rounded-md !bg-blue-600 hover:!bg-blue-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">
                            <div className="formkit-spinner">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                            Subscribe to the newsletter
                        </button>
                    </div>
                </form>
                <svg
                    viewBox="0 0 1024 1024"
                    className="absolute left-1/2 top-1/2 -z-10 h-[64rem] w-[64rem] -translate-x-1/2 [mask-image:radial-gradient(closest-side,white,transparent)]"
                    aria-hidden="true"
                >
                    <circle cx={512} cy={512} r={512} fill="url(#827591b1-ce8c-4110-b064-7cb85a0b1217)"
                            fillOpacity="1" />
                    <defs>
                        <radialGradient id="827591b1-ce8c-4110-b064-7cb85a0b1217">
                            <stop stopColor="#7775D6" />
                            <stop offset={1} stopColor="#3AA9E9" />
                        </radialGradient>
                    </defs>
                </svg>
            </div>
        </div>
    );
}
