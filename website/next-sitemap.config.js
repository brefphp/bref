/** @type {import('next-sitemap').IConfig} */
module.exports = {
    siteUrl: process.env.SITE_URL || 'https://bref.sh',
    generateRobotsTxt: true,
    // Newsletter subscribe landing pages: linked from the migration email and Mailcoach redirects only.
    // Vendor sponsorship: shared directly with vendors.
    exclude: ['/news/subscribe', '/news/subscribe/*', '/vendor-sponsorship'],
}
