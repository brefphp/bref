/** @type {import('next-sitemap').IConfig} */
module.exports = {
    siteUrl: process.env.SITE_URL || 'https://bref.sh',
    generateRobotsTxt: true,
    // Newsletter subscribe landing pages: linked from the migration email and Mailcoach redirects only
    exclude: ['/news/subscribe', '/news/subscribe/*'],
}
