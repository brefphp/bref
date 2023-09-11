import { graphql } from '@octokit/graphql';

const nonGitHubSponsors = [
    {
        isActive: true,
        sponsorEntity: {
            __typename: 'Organization',
            login: 'whilenull',
            name: 'Null',
            websiteUrl: 'https://null.tc/?ref=bref',
        },
        isOneTimePayment: false,
    },
    {
        isActive: true,
        sponsorEntity: {
            __typename: 'Organization',
            login: 'CraftCMS',
            name: 'CraftCMS',
            websiteUrl: 'https://craftcms.com/?ref=bref.sh',
        },
        isOneTimePayment: false,
    },
    {
        isActive: true,
        sponsorEntity: {
            __typename: 'Organization',
            login: 'ShippyPro',
            name: 'ShippyPro',
            websiteUrl: 'https://www.shippypro.com/?ref=bref.sh',
        },
        isOneTimePayment: false,
    },
    {
        isActive: true,
        sponsorEntity: {
            __typename: 'Organization',
            login: 'Tideways',
            name: 'Tideways',
            websiteUrl: 'https://tideways.com/?ref=bref',
        },
        isOneTimePayment: false,
    },
    {
        isActive: true,
        sponsorEntity: {
            __typename: 'Organization',
            login: 'MyBuilder',
            name: 'MyBuilder',
            websiteUrl: 'https://www.mybuilder.com/?ref=bref.sh',
        },
        isOneTimePayment: false,
    },
];

export async function getSponsors() {
    let githubToken = process.env.GITHUB_TOKEN_READ;
    if (!githubToken) {
        githubToken = process.env.GITHUB_TOKEN;
    }
    if (!githubToken) {
        throw new Error('Missing GITHUB_TOKEN or GITHUB_TOKEN_READ');
    }

    const sponsorsQuery = `
    {
      viewer {
        sponsorshipsAsMaintainer(activeOnly: false, first: 100) {
          totalCount
          edges {
            node {
              isActive
              isOneTimePayment
              sponsorEntity {
                __typename
                ... on User {
                  name
                  login
                  websiteUrl
                  twitterUsername
                }
                ... on Organization {
                  name
                  login
                  websiteUrl
                  twitterUsername
                }
              }
            }
          }
        }
      }
    }
    `;
    const sponsorsResponse = await graphql(sponsorsQuery, {
        headers: {
            authorization: `token ${githubToken}`
        }
    });

    return [
        ...nonGitHubSponsors,
        ...sponsorsResponse.viewer.sponsorshipsAsMaintainer.edges.map(({ node }) => node),
    ];
}
