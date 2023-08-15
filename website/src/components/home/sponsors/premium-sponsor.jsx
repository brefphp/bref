import Image from 'next/image';

export default function PremiumSponsor({ src, alt, href, oneTime }) {
    return (
        <a className="relative bg-gray-400/5 p-8 sm:p-10 flex justify-center items-center"
           href={href} title={alt}>
            <Image
                className="max-h-12 max-w-[10rem] w-full object-contain"
                src={src}
                alt={alt}
            />
            { oneTime && <div className="absolute right-0 bottom-0 mb-2 mr-2 text-xs text-gray-400">
                * one-time sponsor
            </div>}
        </a>
    );
}
