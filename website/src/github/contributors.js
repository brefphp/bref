export async function getContributors(repository) {
    // Retrieve the list of contributors from the GitHub REST API
    // See https://docs.github.com/en/rest/reference/repos#list-repository-contributors
    const contributors = [];
    let page = 1;
    let hasNextPage = true;
    while (hasNextPage) {
        const response = await fetch(`https://api.github.com/repos/${repository}/contributors?page=${page}&per_page=100`);
        const data = await response.json();
        contributors.push(...data.map(({ login, html_url, contributions }) => ({ login, html_url, contributions })));
        hasNextPage = response.headers.get('Link')?.includes('rel="next"');
        page++;
    }
    return contributors;
}

export function mergeAndSortContributors(contributors) {
    // Merge duplicates and sum contributions
    const contributorsMap = {};
    contributors.forEach(contributor => {
        if (!contributorsMap[contributor.login]) {
            contributorsMap[contributor.login] = contributor;
        } else {
            contributorsMap[contributor.login].contributions += contributor.contributions;
        }
    });
    return Object.values(contributorsMap)
        .sort((a, b) => b.contributions - a.contributions);
}