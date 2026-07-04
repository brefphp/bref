preview: node_modules
	$(MAKE) preview-parallel-jobs -j2
preview-parallel-jobs: live-sync-doc-files next-dev
live-sync-doc-files:
	npx sync-directory ../docs content/docs --deleteOrphaned --watch
next-dev:
	npm run dev

# Generate and deploy the production version of the website
website: node_modules content/docs
	npm run build

content/docs:
	cp -r ../docs content/docs

node_modules:
	npm install

check-links:
	node link-checker/index.js https://bref-website.vercel.app > links.log
