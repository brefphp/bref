const fs = require('fs');
const path = require('path');

async function runConsole(serverless, options) {
    const region = serverless.getProvider('aws').getRegion();
    // Override CLI options for `sls invoke`
    options.function = options.function || getConsoleFunction(serverless, region);
    options.type = 'RequestResponse';
    options.data = options.args;
    options.log = true;
    // Run `sls invoke`
    await serverless.pluginManager.spawn('invoke');
}

function getConsoleFunction(serverless, region) {
    const consoleLayerArn = getConsoleLayerArn(region);

    const functions = serverless.service.functions;
    const consoleFunctions = [];
    for (const [functionName, functionDetails] of Object.entries(functions)) {
        if (functionDetails.layers?.includes(consoleLayerArn)) {
            consoleFunctions.push(functionName);
        }
    }
    if (consoleFunctions.length === 0) {
        throw new serverless.classes.Error('This command invokes the Lambda "console" function, but no function was found with the "console" layer');
    }
    if (consoleFunctions.length > 1) {
        throw new serverless.classes.Error('More than one function contains the console layer: cannot automatically run it. Please provide a function name using the --function option.');
    }
    return consoleFunctions[0];
}

function getConsoleLayerArn(region) {
    const json = fs.readFileSync(path.join(__dirname, '../layers.json'));
    const layers = JSON.parse(json.toString());
    const version = layers.console[region];
    return `arn:aws:lambda:${region}:534081306603:layer:console:${version}`;
}

module.exports = {runConsole};
