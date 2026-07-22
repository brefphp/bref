import { cache } from 'react';
import { getContributors, mergeAndSortContributors } from '../../github/contributors';
import { getSponsors } from '../../github/sponsors';

// Fetch once per render, shared across the three async Server Components below.
const loadSponsors = cache(async () => {
    try {
        return await getSponsors();
    } catch (e) {
        console.error(e);
        return [];
    }
});

const loadContributors = cache(async () => {
    try {
        return mergeAndSortContributors([
            ...(await getContributors('brefphp/bref')),
            ...(await getContributors('brefphp/aws-lambda-layers')),
            ...(await getContributors('brefphp/laravel-bridge')),
            ...(await getContributors('brefphp/symfony-bridge')),
            ...(await getContributors('brefphp/extra-php-extensions')),
            ...(await getContributors('brefphp/examples')),
        ]);
    } catch (e) {
        console.error(e);
        return [];
    }
});

function SponsorsList({ sponsors }) {
    const sortedSponsors = sponsors
        // Fill missing names with the login
        .map(sponsor => {
            sponsor.sponsorEntity.name = sponsor.sponsorEntity.name ?? sponsor.sponsorEntity.login;
            return sponsor;
        })
        // Sort organizations first, then alphabetically by name second
        .sort(({ sponsorEntity: a }, { sponsorEntity: b }) => {
            // Same type
            if (a.__typename === b.__typename) {
                return a.name.localeCompare(b.name);
            }
            // Different types
            return a.__typename === 'Organization' ? -1 : 1;
        });
    return <p>
        {sortedSponsors.map(({ sponsorEntity, isOneTimePayment }, i) => (
            <span key={sponsorEntity.login}>
                {i > 0 && ", "}
                <a className="!no-underline !text-inherit"
                   href={sponsorEntity.websiteUrl || `https://github.com/${sponsorEntity.login}`}>
                    {sponsorEntity.name}
                </a>
                {isOneTimePayment && ' (one-time sponsor)'}
            </span>
        ))}
        .
    </p>;
}

export async function CurrentSponsors() {
    const sponsors = await loadSponsors();
    return <SponsorsList sponsors={sponsors.filter(({ isActive }) => isActive)} />;
}

export async function PastSponsors() {
    const sponsors = await loadSponsors();
    return <SponsorsList sponsors={sponsors.filter(({ isActive }) => !isActive)} />;
}

export async function ContributorsList() {
    const contributors = await loadContributors();
    return <p>
        {contributors.map(({ login, html_url, contributions }, i) => (
            <span key={login}>
                {i > 0 && ", "}
                <a className="!no-underline !text-inherit" href={html_url}>
                    {login}
                </a>
                {contributions > 1 && ` (${contributions})`}
            </span>
        ))}
        .
    </p>;
}
