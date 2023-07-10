website-preview: node_modules
	$(MAKE) website-preview-parallel-jobs -j2
website-preview-parallel-jobs: website-live-sync-doc-files website-next-dev
website-live-sync-doc-files:
	npx sync-directory ../docs src/pages/docs --deleteOrphaned --watch
website-next-dev:
	npm run dev

# Generate and deploy the production version of the website using http://couscous.io
website: node_modules src/pages/docs
	npm run build

src/pages/docs:
	cp -r ../docs src/pages/docs

node_modules:
	npm install
