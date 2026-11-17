/**
 * @param {import('./serverless').Serverless} serverless
 * @param {import('./serverless').CliOptions} options
 */
async function runConsole(serverless, options) {
    const functionName = /** @type {string} */ (options.function || getConsoleFunction(serverless));
    const functionConfig = (serverless.service.functions || {})[functionName];
    if (!functionConfig) {
        throw new serverless.classes.Error(`Function "${functionName}" doesn't exist in this service`);
    }

    const provider = serverless.getProvider('aws');
    if (typeof provider.getAwsSdkV3Config !== 'function') {
        throw new serverless.classes.Error('The `bref:cli` command requires a more recent version of the `osls` package (the Serverless Framework fork maintained by Bref), please update it');
    }
    // The AWS SDK of the Serverless Framework
    const {LambdaClient, InvokeWithResponseStreamCommand} = /** @type {NodeModule} */ (require.main).require('@aws-sdk/client-lambda');
    const lambda = new LambdaClient(await provider.getAwsSdkV3Config());

    // The output is streamed: it is displayed while the command runs
    const response = await lambda.send(new InvokeWithResponseStreamCommand({
        FunctionName: functionConfig.name,
        Payload: Buffer.from(JSON.stringify({cli: options.args || '', stream: true})),
    }));
    let error;
    for await (const event of response.EventStream) {
        if (event.PayloadChunk && event.PayloadChunk.Payload) {
            process.stdout.write(event.PayloadChunk.Payload);
        }
        if (event.InvokeComplete && event.InvokeComplete.ErrorCode) {
            // The stream is read to the end before failing: the connection would stay open
            error = errorMessage(event.InvokeComplete);
        }
    }
    if (error) {
        throw new serverless.classes.Error(error);
    }
}

/**
 * @param {{ErrorCode?: string, ErrorDetails?: string}} invokeComplete
 */
function errorMessage(invokeComplete) {
    try {
        // E.g. "The command exited with code 1"
        return JSON.parse(invokeComplete.ErrorDetails || '').errorMessage || invokeComplete.ErrorCode;
    } catch {
        return invokeComplete.ErrorCode;
    }
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
