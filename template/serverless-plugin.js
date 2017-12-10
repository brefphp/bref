'use strict';

const execSync = require('child_process').execSync;

class PhpServerlessPlugin {
    constructor(serverless, options) {
        this.serverless = serverless;
        this.options = options;
        this.hooks = {
            'deploy:createDeploymentArtifacts': this.build.bind(this),
        };
    }

    /**
     * Build step.
     */
    build() {
        const service = this.serverless.service;
        const scripts = service.custom && service.custom.phpbuild;

        // Download PHP binary
        execSync('vendor/bin/phplambda package', {stdio: 'inherit' });

        // If there are build hooks, execute them
        if (scripts instanceof Array) {
        for (const script of scripts) {
                execSync(script, {stdio: 'inherit' });
            }
        }
    }
}

module.exports = PhpServerlessPlugin;
