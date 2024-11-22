import Image from 'next/image';
import neil from './testimonials/neil.jpg';
import geeh from './testimonials/geeh.jpg';
import paul from './testimonials/paul.jpg';
import marco from './testimonials/marco.jpg';
import robdwaller from './testimonials/robdwaller.jpg';
import aranreeks from './testimonials/aranreeks.jpg';
import nyholm from './testimonials/nyholm.jpg';
import zmalter from './testimonials/zmalter.jpg';
import simon from './testimonials/simon.jpg';
import lorenzo from './testimonials/lorenzo.jpg';
import jlaswell from './testimonials/jlaswell.png';

const testimonials = [
    {
        body: 'Bref is excellent. We\'ve been running a Laravel app with it since 2020 and it\'s currently handling over <strong>160 million requests</strong> per month without a hiccup.',
        author: {
            name: 'Neil Morgan',
            handle: 'neil-r-morgan',
            link: 'https://www.linkedin.com/in/neil-r-morgan/',
            image: neil,
        },
    },
    {
        body: 'Bref has been a boon for running our customer\'s applications. We\'ve had a Laravel API on Bref for the last 12 months serve over <strong>25 million requests</strong> with an average response time of 50ms.',
        author: {
            name: 'Paul Giberson',
            handle: 'HalasLabs',
            link: 'https://twitter.com/HalasLabs/status/1638650910971932672',
            image: paul,
        },
    },
    {
        body: 'Just finished migrating our production from Heroku to AWS Lambda via Bref. It\'ll save us around <strong>$2k a year</strong> 🤯',
        author: {
            name: 'Zach Malter',
            handle: 'zmalter99',
            link: 'https://twitter.com/zmalter99/status/1671228229317689367',
            image: zmalter,
        },
    },
    {
        body: 'Every time I throw something up onto AWS Lambda in PHP using Bref I marvel at how mega-useful it is. If you haven’t checked out Bref you’re probably missing out',
        author: {
            name: 'Gary Hockin',
            handle: 'GeeH',
            link: 'https://twitter.com/GeeH/status/1335909653897752576',
            image: geeh,
        },
    },
    {
        body: 'When your production website with Symfony, API Platform and Bref handles more than <strong>500 simultaneous connections</strong> without flinching…',
        author: {
            name: '$!m0n',
            handle: '__si_mon',
            link: 'https://twitter.com/__si_mon/status/1616778693212348416',
            image: simon,
        },
    },
    {
        body: 'I’ve been running APIs and websites with Bref in prod for over a year now. It is indeed as simple as you describe it.',
        author: {
            name: 'Tobias Nyholm',
            handle: 'TobiasNyholm',
            link: 'https://twitter.com/TobiasNyholm/status/1292027581986934785',
            image: nyholm,
        },
    },
    {
        body: 'There is something amazing and magical about Bref and serverless deploying stuff to the cloud.',
        author: {
            name: 'Rob Waller',
            handle: 'RobDWaller',
            link: 'https://twitter.com/RobDWaller/status/1484569852694118406',
            image: robdwaller,
        },
    },
    {
        body: 'Happily using Bref since 2019 to process <strong>millions of requests, jobs and scheduled tasks</strong>. It powers the best technical accomplishment of my career and has made me a better software engineer and open-source contributor.',
        author: {
            name: 'Marco Deleu',
            handle: 'deleugyn',
            link: 'https://twitter.com/deleugyn',
            image: marco,
        },
    },
    {
        body: 'The team embraced Bref with lightning speed! We were able to roll out the first parts of our new <strong>event-driven architecture within days</strong>, crafting new features in a mere fraction of the time it used to take — truly mind-blowing!',
        author: {
            name: 'Lorenzo Rogai',
            handle: 'lorenzo-rogai',
            link: 'https://www.linkedin.com/in/lorenzo-rogai/',
            image: lorenzo,
        },
    },
    {
        body: 'An incredible project and one we\'re very proud to use in production for a recent eCommerce project we launched that saw <strong>32 million</strong> Lambda invocations last month.',
        author: {
            name: 'Aran Reeks',
            handle: 'AranReeks',
            link: 'https://twitter.com/AranReeks/status/1332467843254919168',
            image: aranreeks,
        },
    },
    {
        body: 'Bref defines how PHP should run on AWS Lambda. At Voxie, we sponsor Bref because it\'s more than a runtime — it\'s the best foundation for building scalable, efficient products on AWS Lambda.',
        author: {
            name: 'John Laswell',
            handle: 'john_laswell',
            link: 'https://www.linkedin.com/feed/update/urn:li:activity:7265474236402536448/',
            image: jlaswell,
        },
    },
    // More testimonials...
]

export default function Testimonials() {
    return (
        <div className="home-container home-section">
            <h2 className="text-center text-3xl font-black leading-8 text-gray-900">
                Happy users and community
            </h2>
            <div className="mx-auto mt-16 flow-root max-w-2xl sm:mt-20 lg:mx-0 lg:max-w-none">
                <div className="-mt-8 sm:-mx-4 sm:columns-2 sm:text-[0] lg:columns-3">
                    {testimonials.map((testimonial) => (
                        <div key={testimonial.author.handle} className="pt-8 sm:inline-block sm:w-full sm:px-4">
                            <figure className="rounded-2xl bg-gray-400/10 p-8 text-sm leading-6">
                                <blockquote className="text-gray-900">
                                    <p dangerouslySetInnerHTML={{__html: `“${testimonial.body}”`}}></p>
                                </blockquote>
                                <figcaption className="mt-6 flex items-center gap-x-4">
                                    {testimonial.author.imageUrl ? (
                                    <img className="h-10 w-10 rounded-full bg-gray-400/10"
                                         src={testimonial.author.imageUrl} alt={testimonial.author.name} />
                                    ) : (
                                    <Image className="h-10 w-10 rounded-full bg-gray-400/10"
                                           src={testimonial.author.image} alt={testimonial.author.name} />
                                    )}
                                    <div>
                                        <div className="font-semibold text-gray-900">{testimonial.author.name}</div>
                                        <a href={testimonial.author.link} className="text-gray-600">{`@${testimonial.author.handle}`}</a>
                                    </div>
                                </figcaption>
                            </figure>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    )
}
