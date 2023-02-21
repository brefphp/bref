const fs = require('fs');

function warnIfUsingSecretsWithoutTheBrefDependency(serverless, log) {
    const config = serverless.service;
    const allVariables = [];

    const check = ([name, value]) => {
        if (typeof value === 'string' && value.startsWith('bref-ssm:')) {
            allVariables.push(`${name}: ${value}`);
        }
    };
    Object.entries(config.provider.environment || {}).forEach(check);
    Object.values(config.functions).forEach(f => Object.entries(f.environment || {}).forEach(check));

    if (allVariables.length > 0) {
        // Check if the bref/secrets-loader dependency is installed in composer.json
        if (! fs.existsSync('composer.json')) {
            return;
        }
        const composerJson = JSON.parse(fs.readFileSync('composer.json', 'utf8'));
        const dependencies = Object.keys(composerJson.require || {});
        if (dependencies.includes('bref/secrets-loader')) {
            return;
        }

        log.warning(`The following environment variables use the "bref-ssm:" prefix, but the "bref/secrets-loader" dependency is not installed via "composer.json".`);
        allVariables.forEach(variable => log.warning(`    ${variable}`));
        log.warning(`The "bref/secrets-loader" dependency is required to use the "bref-ssm:" prefix. Install it by running:`);
        log.warning(`    composer require bref/secrets-loader`);
        log.warning(`Learn more at https://bref.sh/docs/environment/variables.html#secrets`);
        log.warning();
    }
}

module.exports = {warnIfUsingSecretsWithoutTheBrefDependency};
