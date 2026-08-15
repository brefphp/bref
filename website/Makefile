preview: node_modules
	$(MAKE) preview-parallel-jobs -j2
preview-parallel-jobs: live-sync-doc-files next-dev
live-sync-doc-files:
	npx sync-directory ../docs src/pages/docs --deleteOrphaned --watch
next-dev:
	npm run dev

# Generate and deploy the production version of the website
website: node_modules src/pages/docs
	npm run build

src/pages/docs:
	cp -r ../docs src/pages/docs

node_modules:
	npm install

# Usage: `make check-links` or `make check-links URL=http://localhost:8000`
check-links: URL ?= https://bref-website.vercel.app
check-links:
	node link-checker/index.js $(URL) > links.log
