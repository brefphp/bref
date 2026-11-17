/**
 * @param {import('./serverless').Serverless} serverless
 * @param {import('./serverless').CliOptions} options
 * @param {import('./serverless').Logger} log
 */
async function runConsole(serverless, options, log) {
    const functionName = /** @type {string} */ (options.function || getConsoleFunction(serverless));
    const functionConfig = (serverless.service.functions || {})[functionName];
    if (!functionConfig) {
        throw new serverless.classes.Error(`Function "${functionName}" doesn't exist in this service`);
    }

    const provider = serverless.getProvider('aws');
    if (typeof provider.getAwsSdkV3Config !== 'function') {
        log.warning('Update the `osls` package (the Serverless Framework fork maintained by Bref) to see the output of the command while it runs.');
        log.warning();
        // Run `sls invoke`: the output is displayed at the end
        options.function = functionName;
        options.type = 'RequestResponse';
        options.data = options.args;
        options.log = true;
        await serverless.pluginManager.spawn('invoke');
        return;
    }

    // The AWS SDK of the Serverless Framework
    const {LambdaClient, InvokeWithResponseStreamCommand} = /** @type {NodeModule} */ (require.main).require('@aws-sdk/client-lambda');
    const lambda = new LambdaClient(await provider.getAwsSdkV3Config());

    const error = await streamCommand(lambda, new InvokeWithResponseStreamCommand({
        FunctionName: functionConfig.name,
        Payload: Buffer.from(JSON.stringify(commandEvent(options.args))),
    }), (chunk) => process.stdout.write(chunk));
    if (error) {
        throw new serverless.classes.Error(error);
    }
}

/**
 * The event that asks the console runtime to stream the output of the command.
 *
 * @param {unknown} args
 */
function commandEvent(args) {
    return {cli: typeof args === 'string' ? args : '', stream: true};
}

/**
 * Write the output of the command as it is received.
 *
 * @param {{send: (command: unknown) => Promise<{EventStream: AsyncIterable<any>}>}} lambda
 * @param {unknown} command The InvokeWithResponseStream command
 * @param {(chunk: Uint8Array) => void} write
 * @return {Promise<string|undefined>} Why the command failed, if it did
 */
async function streamCommand(lambda, command, write) {
    const response = await lambda.send(command);
    let error;
    for await (const event of response.EventStream) {
        if (event.PayloadChunk && event.PayloadChunk.Payload) {
            write(event.PayloadChunk.Payload);
        }
        if (event.InvokeComplete && event.InvokeComplete.ErrorCode) {
            // The stream is read to the end before failing: the connection would stay open
            error = errorMessage(event.InvokeComplete);
        }
    }
    return error;
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

module.exports = {runConsole, commandEvent, streamCommand};
