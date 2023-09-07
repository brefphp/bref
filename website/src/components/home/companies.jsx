import Image from 'next/image';
import phpStanLogo from './companies/phpstan.svg';
import bcastLogo from './companies/bcast.svg';
import myBuilderLogo from './companies/mybuilder.svg';
import neuralLoveLogo from './companies/neural-love.svg';
import enopteaLogo from './companies/enoptea.png';

export default function Companies() {
    return (
        <div className="home-container home-section">
            <h2 className="text-center text-3xl font-black leading-8 text-gray-900">
                Used in production at
            </h2>
            <div
                className="mt-16 -mx-6 grid grid-cols-2 gap-0.5 overflow-hidden sm:mx-0 sm:rounded-2xl md:grid-cols-3">
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <Image
                        className="max-h-12 max-w-[10rem] w-full object-contain grayscale opacity-80"
                        src={phpStanLogo}
                        alt="PhpStan"
                    />
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <Image
                        className="max-h-12 max-w-[10rem] w-full object-contain grayscale opacity-80"
                        src={bcastLogo}
                        alt="bCast.fm"
                    />
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <Image
                        className="max-h-12 max-w-[10rem] w-full object-contain grayscale opacity-80"
                        src={myBuilderLogo}
                        alt="MyBuilder"
                    />
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <Image
                        className="max-h-12 max-w-[10rem] w-full object-contain grayscale opacity-80"
                        src={neuralLoveLogo}
                        alt="neural.love"
                    />
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <Image
                        className="max-h-12 max-w-[10rem] w-full object-contain grayscale opacity-80"
                        src={enopteaLogo}
                        alt="Enoptea"
                    />
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <a href="https://www.phpjobs.app/" className="text-2xl font-bold text-gray-600">phpjobs.app</a>
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <a href="https://externals.io" className="text-2xl font-bold text-gray-600">externals.io</a>
                </div>
                <div className="bg-gray-400/10 p-8 sm:p-10 flex justify-center items-center">
                    <a href="https://github.com/brefphp/bref/issues/267" className="text-gray-600">add your company</a>
                </div>
            </div>
        </div>
    );
}
