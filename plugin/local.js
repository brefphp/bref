const {spawnSync} = require('child_process');
const path = require('path');
const fs = require('fs');

/**
 * @param {import('./serverless').Serverless} serverless
 * @param {import('./serverless').CliOptions} options
 */
function runLocal(serverless, options) {
    if (options.data && options.path) {
        throw new serverless.classes.Error('You cannot provide both --data and --path');
    }

    let data = options.data;
    if (!data && options.path) {
        if (typeof options.path !== 'string') {
            throw new serverless.classes.Error('The --path option must be a string');
        }
        data = fs.readFileSync(options.path).toString();
    }

    // @ts-ignore
    const fn = serverless.service.getFunction(options.function);

    const args = [
        path.join(__dirname, '../src/bref-local'),
        fn.handler,
        data || '',
    ];
    spawnSync('php', args, {
        stdio: 'inherit',
    });
}

module.exports = {runLocal};
