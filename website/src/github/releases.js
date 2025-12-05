export async function getReleases(repository, limit = 20) {
    const token = process.env.GITHUB_TOKEN_CHANGELOGS;
    if (!token) {
        console.error('GITHUB_TOKEN_CHANGELOGS is not set. You may hit rate limits when fetching releases from GitHub API.');
        return;
    }

    const response = await fetch(`https://api.github.com/repos/${repository}/releases?per_page=${limit}`, {
        headers: {
            'Authorization': `token ${token}`,
        },
    });
    if (!response.ok) {
        console.error(`Failed to fetch releases from ${repository}: ${response.status} ${response.statusText}`);
        return [];
    }
    
    const releases = await response.json();
    
    return releases.map(release => ({
        id: release.id,
        name: release.name,
        tagName: release.tag_name,
        body: release.body,
        publishedAt: release.published_at,
        htmlUrl: release.html_url,
        draft: release.draft,
        prerelease: release.prerelease
    }));
}