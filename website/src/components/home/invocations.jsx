import Link from 'next/link';
import Image from 'next/image';
import treezorLogo from './companies/treezor.svg';
import minutesLogo from './companies/20minutes.svg';
import crowcubeLogo from './companies/crowdcube.svg';
import gulliLogo from './companies/gulli.svg';
import phpStanLogo from './companies/phpstan.svg';
import bcastLogo from './companies/bcast.svg';
import enopteaLogo from './companies/enoptea.png';
import suaMusicaLogo from './companies/sua-musica.svg';
import myBuilderLogo from './companies/mybuilder.svg';
import craftCmsLogo from './sponsors/logo-craft-cms.png';
import voxieLogo from './companies/voxie.svg';
import spreakerLogo from './sponsors/logo-spreaker.svg';

const companies = [
    { src: crowcubeLogo, alt: 'Crowdcube', link: 'https://www.crowdcube.com/', classes: '!h-10' },
    { src: suaMusicaLogo, alt: 'SuaMusica', link: 'https://suamusica.com.br/', classes: 'invert' },
    { src: minutesLogo, alt: '20minutes.fr', link: 'https://www.20minutes.fr/', classes: 'brightness-0 invert' },
    { src: voxieLogo, alt: 'Voxie', link: 'https://voxie.com/', classes: 'brightness-10 invert !h-10' },
    { src: phpStanLogo, alt: 'PhpStan', link: 'https://phpstan.org/', classes: 'brightness-0 invert !h-6' },
    { src: bcastLogo, alt: 'bCast.fm', link: 'https://bcast.fm/', classes: 'brightness-0 invert' },
    { src: craftCmsLogo, alt: 'Craft CMS', link: 'https://craftcms.com/', classes: 'brightness-0 invert !h-9' },
    { src: myBuilderLogo, alt: 'MyBuilder', link: 'https://www.mybuilder.com/', classes: 'brightness-0 invert !h-7' },
    { src: enopteaLogo, alt: 'Enoptea', link: 'https://www.enoptea.fr/', classes: 'brightness-0 invert !h-7' },
    { src: spreakerLogo, alt: 'Spreaker', link: 'https://www.spreaker.com/', classes: 'brightness-0 invert !h-9' },
    { src: gulliLogo, alt: 'Gulli.fr', link: 'https://www.gulli.fr/', classes: 'grayscale brightness-200' },
    { src: treezorLogo, alt: 'Treezor', link: 'https://www.treezor.com/', classes: 'brightness-0 invert !h-7' }
];

export default function Invocations({ invocations }) {
    return (
        <div id="invocations" className="home-container home-section !px-0 sm:!px-6 !py-12 sm:!py-16">
            <div
                className="relative isolate overflow-hidden bg-gray-900 px-6 py-10 text-center shadow-2xl sm:rounded-3xl sm:px-16">
                <h2 className="mx-auto max-w-2xl text-3xl font-black tracking-tight text-white sm:text-5xl">
                    {invocations?.toLocaleString('en-US')}
                </h2>
                <p className="mx-auto mt-3 max-w-xl text-lg text-gray-300">
                    requests, jobs, and messages handled with Bref in the <strong className="text-white">last 30 days</strong>
                </p>
                <p className="mx-auto max-w-xl text-lg text-gray-300">
                    across thousands of companies
                </p>
                <div className="mt-8 mx-auto flex justify-center flex-wrap w-full items-center gap-6 lg:gap-x-12">
                    {companies.map(company => (
                        <Link key={company.link} href={company.link} rel="noopener nofollow"
                              className="h-9 lg:h-12 flex items-center justify-center overflow-hidden">
                            <Image
                                className={`h-full w-auto object-contain opacity-50 hover:opacity-100 ${company.classes}`}
                                src={company.src}
                                alt={company.alt}
                            />
                        </Link>
                    ))}
                </div>
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
