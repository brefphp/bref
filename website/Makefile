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

check-links:
	node link-checker/index.js https://website-v3--bref-website-v2.netlify.app/docs
