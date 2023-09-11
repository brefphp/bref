import Image from 'next/image';
import phpStanLogo from './companies/phpstan.svg';
import bcastLogo from './companies/bcast.svg';
import myBuilderLogo from './companies/mybuilder.svg';
import neuralLoveLogo from './companies/neural-love.svg';
import enopteaLogo from './companies/enoptea.png';
import gulliLogo from './companies/gulli.svg';

export default function Companies() {
    return (
        <div className="bg-gray-900 py-24 sm:py-32">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="grid grid-cols-1 items-center gap-x-8 gap-y-16 lg:grid-cols-2">
                    <div className="mx-auto w-full max-w-xl lg:mx-0">
                        <h2 className="text-3xl font-black leading-8 text-white">
                            Used in production around the world
                        </h2>
                        <p className="mt-6 text-lg leading-8 text-gray-300">
                            Get started with Bref on your own, or get in touch for support and consulting.
                        </p>
                        <div className="mt-8 flex items-center gap-x-6">
                            <a
                                href="/docs/"
                                className="rounded-md bg-blue-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
                            >
                                Documentation
                            </a>
                            <a href="/plans" className="text-sm font-semibold text-white">
                                Support & consulting <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                    <div className="mx-auto grid w-full max-w-xl grid-cols-2 items-center gap-y-12 sm:gap-y-14 lg:mx-0 lg:max-w-none lg:pl-8">
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert"
                            src={phpStanLogo}
                            alt="PhpStan"
                        />
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert"
                            src={bcastLogo}
                            alt="bCast.fm"
                        />
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert"
                            src={myBuilderLogo}
                            alt="MyBuilder"
                        />
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left grayscale brightness-200"
                            src={gulliLogo}
                            alt="Gulli.fr"
                        />
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert"
                            src={neuralLoveLogo}
                            alt="neural.love"
                        />
                        <Image
                            className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert"
                            src={enopteaLogo}
                            alt="Enoptea"
                        />
                        <div className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert">
                            <span className="text-2xl font-bold text-white">phpjobs.app</span>
                        </div>
                        <div className="max-h-12 max-w-[10rem] w-full object-contain object-left brightness-0 invert">
                            <span className="text-2xl font-bold text-white">externals.io</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}