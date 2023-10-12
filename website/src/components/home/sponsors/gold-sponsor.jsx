import Image from 'next/image';

export default function GoldSponsor({ src, alt, href, imgClass }) {
    return (
        <a className="bg-gray-400/10 p-4 sm:p-6 flex justify-center items-center"
           href={href} title={alt}>
            <Image
                className={imgClass + " max-h-12 max-w-[10rem] w-full object-contain"}
                src={src}
                alt={alt}
            />
        </a>
    );
}
