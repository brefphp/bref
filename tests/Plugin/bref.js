'use strict';

// The Bref plugin lives at the repository root, outside this test service
// directory. Since osls v4 (PR #395), `plugins:` entries must be npm package
// names or `./` paths that stay inside the service directory, so `../../index.js`
// is no longer accepted. This shim re-exports the plugin from a path the tests
// can reference as `./bref.js`.
module.exports = require('../../index.js');
