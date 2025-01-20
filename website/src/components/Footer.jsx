import { TwitterIcon } from './icons/TwitterIcon';
import { GitHubIcon } from './icons/GitHubIcon';
import { SlackIcon } from './icons/SlackIcon';

const navigation = {
    main: [
        { name: 'Home', href: '/' },
        { name: 'Documentation', href: '/docs/' },
        { name: 'Slack', href: 'https://bref.sh/slack' },
        { name: 'News', href: '/news/' },
        { name: 'Support', href: '/support' },
        { name: 'Bref Cloud', href: '/cloud' },
        { name: 'Credits', href: '/credits' },
    ],
    social: [
        {
            name: 'GitHub',
            href: 'https://github.com/brefphp/bref',
            icon: GitHubIcon,
        },
        {
            name: 'Twitter',
            href: 'https://twitter.com/brefphp',
            icon: TwitterIcon,
        },
        {
            name: 'Slack',
            href: '/slack',
            icon: SlackIcon,
        },
    ],
}

export default function Footer() {
    return (
        <footer className="bg-white">
            <div className="mx-auto max-w-7xl overflow-hidden px-6 py-20 sm:py-24 lg:px-8">
                <nav className="-mb-6 columns-2 sm:flex sm:justify-center sm:space-x-12" aria-label="Footer">
                    {navigation.main.map((item) => (
                        <div key={item.name} className="pb-6">
                            <a href={item.href} className="text-sm leading-6 text-gray-600 hover:text-gray-900">
                                {item.name}
                            </a>
                        </div>
                    ))}
                </nav>
                <div className="mt-10 flex justify-center space-x-10">
                    {navigation.social.map((item) => (
                        <a key={item.name} href={item.href} className="text-gray-400 hover:text-gray-500" title={item.name}>
                            <span className="sr-only">{item.name}</span>
                            <item.icon className="h-6 w-6" aria-hidden="true" />
                        </a>
                    ))}
                </div>
                <p className="mt-10 text-center text-xs leading-5 text-gray-500">
                    &copy; {new Date().getFullYear()}{' '}
                    <a href="https://mnapoli.fr" className="hover:underline">
                        Matthieu Napoli
                    </a>
                </p>
            </div>
        </footer>
    )
}
