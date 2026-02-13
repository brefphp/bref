/**
 * @param {import('./serverless').Serverless} serverless
 * @param {import('./serverless').CliOptions} options
 */
async function runConsole(serverless, options) {
    // Override CLI options for `sls invoke`
    options.function = options.function || getConsoleFunction(serverless);
    options.type = 'RequestResponse';
    options.data = options.args;
    options.log = true;
    // Run `sls invoke`
    await serverless.pluginManager.spawn('invoke');
}

/**
 * @param {import('./serverless').Serverless} serverless
 */
function getConsoleFunction(serverless) {
    const functions = serverless.service.functions;
    const consoleFunctions = [];
    for (const [functionName, functionDetails] of Object.entries(functions || {})) {
        // Check for BREF_RUNTIME environment variable (set by Bref plugin for php-XX-console runtimes)
        const brefRuntime = functionDetails.environment && functionDetails.environment.BREF_RUNTIME;
        if (brefRuntime === 'Bref\\ConsoleRuntime\\Main' || brefRuntime === 'console') {
            consoleFunctions.push(functionName);
        }
    }
    if (consoleFunctions.length === 0) {
        throw new serverless.classes.Error('This command invokes a Lambda console function, but no function was found using the console runtime (e.g. php-84-console)');
    }
    if (consoleFunctions.length > 1) {
        throw new serverless.classes.Error('More than one function uses the console runtime: cannot automatically run it. Please provide a function name using the --function option.');
    }
    return consoleFunctions[0];
}

module.exports = {runConsole};
