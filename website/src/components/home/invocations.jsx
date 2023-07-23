import { useEffect, useState } from 'react';

export default function Invocations() {
    const invocations = 16411284305;
    const [counter, setCounter] = useState(invocations);

    // useEffect(() => {
    //     const timeout = setTimeout(() => {
    //         setCounter(counter + 23);
    //     }, 300);
    //     return () => {
    //         clearTimeout(timeout);
    //     };
    // }, [counter]);

    return (
        <div className="bg-white">
            <div className="mx-auto max-w-7xl py-16 sm:px-6 sm:py-32 lg:px-8">
                <div className="relative isolate overflow-hidden bg-gray-900 px-6 py-24 text-center shadow-2xl sm:rounded-3xl sm:px-16">
                    <h2 className="mx-auto max-w-2xl text-3xl font-black tracking-tight text-white sm:text-5xl">
                        {counter.toLocaleString('en-US')}
                    </h2>
                    <p className="mx-auto mt-2 max-w-xl text-lg leading-8 text-gray-300">
                        requests, jobs, and messages handled with Bref in the <strong>last 30 days</strong>
                    </p>
                    <div className="mt-4 flex items-center justify-center gap-x-6">
                        <a href="#" className="text-sm font-semibold leading-6 text-white">
                            Learn more <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <svg
                        viewBox="0 0 1024 1024"
                        className="absolute left-1/2 top-1/2 -z-10 h-[64rem] w-[64rem] -translate-x-1/2 [mask-image:radial-gradient(closest-side,white,transparent)]"
                        aria-hidden="true"
                    >
                        <circle cx={512} cy={512} r={512} fill="url(#827591b1-ce8c-4110-b064-7cb85a0b1217)" fillOpacity="1" />
                        <defs>
                            <radialGradient id="827591b1-ce8c-4110-b064-7cb85a0b1217">
                                <stop stopColor="#7775D6" />
                                <stop offset={1} stopColor="#3AA9E9" />
                            </radialGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    )
}
