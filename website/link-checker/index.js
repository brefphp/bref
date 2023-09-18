import {Command, Option, runExit} from 'clipanion';
import fetch from 'node-fetch';
import { Parser } from "htmlparser2";

runExit(class HelloCommand extends Command {
    url = Option.String();

    async execute() {
        /** @type {Record<string, boolean>} */
        const links = {};
        const brokenLinks = new Set();
        /** @type {Record<string, string|false>} */
        const pageCache = {};
        await scan(this.context.stdout, this.url, links, brokenLinks, pageCache);

        this.context.stdout.write(`Found ${brokenLinks.size} broken links\n`);
        for (const link of brokenLinks) {
            this.context.stdout.write(`  ${link}\n`);
        }
    }
});

/**
 * @param {Writable} stdout
 * @param {string} url
 * @param {Record<string, boolean>} links
 * @param {Set<string>} brokenLinks
 * @param {Record<string, string|false>} pageCache
 * @returns {Promise<void>}
 */
async function scan(stdout, url, links, brokenLinks, pageCache) {
    // Reserve the link to avoid infinite loops
    if (links[url] === true) {
        return;
    }
    links[url] = true;

    stdout.write(`Scanning ${url}\n`);

    // Cache the page to avoid fetching it twice when it is linked via anchor tags
    const urlWithoutAnchor = url.split('#')[0];
    if (pageCache[urlWithoutAnchor] === undefined) {
        const response = await fetch(url);
        // Ignore redirects to other domains (e.g. https://bref.sh/slack)
        const originalDomain = new URL(url).hostname;
        const finalDomain = new URL(response.url).hostname;
        if (finalDomain !== originalDomain) {
            console.log(`Ignoring ${url} redirecting to ${response.url}`);
            pageCache[urlWithoutAnchor] = false;
            return;
        }
        if (! response.ok) {
            pageCache[urlWithoutAnchor] = false;
        } else {
            pageCache[urlWithoutAnchor] = await response.text();
        }
    }
    const pageBody = pageCache[urlWithoutAnchor];

    if (pageBody === false) {
        stdout.write(`Error: ${url}\n`);
        brokenLinks.add(url);
        return;
    }

    // Extract the anchor link from the url
    const anchorLink = url.split('#')[1] ?? undefined;
    let foundAnchorLink = false;

    const parser = new Parser({
        onopentag(name, attributes) {
            // Search for the anchor link
            if (anchorLink && attributes.id === anchorLink) {
                foundAnchorLink = true;
            }
            // Register new links
            let newLink = attributes.href ?? undefined;
            if (name === 'a' && newLink) {
                // Turn the relative link into an absolute one
                // but avoid double slashes
                newLink = new URL(newLink, url).toString();
                // Ignore external links on a different domain
                const domain = new URL(url).hostname;
                if (! newLink.startsWith(`http://${domain}`) && ! newLink.startsWith(`https://${domain}`)) {
                    return;
                }
                if (links[newLink] !== undefined) {
                    // Ignore already scanned links
                    return;
                }
                stdout.write(`Found new link ${newLink} on ${url}\n`);
                links[newLink] = false;
            }
        },
    });
    parser.write(pageBody);
    parser.end();

    if (anchorLink && ! foundAnchorLink) {
        stdout.write(`Error: anchor link not found: ${anchorLink}\n`);
        brokenLinks.add(url);
    }

    // Scan all the links we found that have not been scanned yet
    const linksToScan = Object.entries(links)
        .filter(([, scanned]) => ! scanned)
        .map(([link]) => link);
    for (const chunk of chunkArray(linksToScan)) {
        const promises = chunk
            .map(link => scan(stdout, link, links, brokenLinks, pageCache));
        await Promise.all(promises);
    }
}

/**
 * @template T
 * @param {T[]} array
 * @param {number} size
 * @returns {T[][]}
 */
function chunkArray(array, size = 4) {
    const results = [];
    while (array.length) {
        results.push(array.splice(0, size));
    }
    return results;
}
