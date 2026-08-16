import fs from 'fs';
import path from 'path';

export default function handler(req, res) {
    const { slug } = req.query;
    const slugPath = Array.isArray(slug) ? slug.join('/') : slug;

    // Try to find the MDX file
    const docsDir = path.join(process.cwd(), 'src/pages/docs');
    let filePath = path.join(docsDir, `${slugPath}.mdx`);

    // If not found, try index.mdx in directory
    if (!fs.existsSync(filePath)) {
        filePath = path.join(docsDir, slugPath, 'index.mdx');
    }

    if (!fs.existsSync(filePath)) {
        return res.status(404).json({ error: 'Page not found' });
    }

    let content = fs.readFileSync(filePath, 'utf8');

    // Strip the frontmatter block
    content = content.replace(/^---\n[\s\S]*?\n---\n/, '');

    // Strip import statements at the top of the file
    content = content.replace(/^import\s+.*?(?:from\s+['"].*?['"])?;?\s*$/gm, '');

    // Clean up excessive blank lines at the start
    content = content.replace(/^\s*\n+/, '');

    res.setHeader('Content-Type', 'text/markdown; charset=utf-8');
    res.status(200).send(content);
}
