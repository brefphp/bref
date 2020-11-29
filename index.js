'use strict';

/**
 * This file declares a plugin for the Serverless framework.
 *
 * This lets us define variables and helpers to simplify creating PHP applications.
 */

class ServerlessPlugin {
    constructor(serverless, options) {
        const fs = require("fs");
        const path = require('path');
        const filename = path.resolve(__dirname, 'layers.json');
        const layers = JSON.parse(fs.readFileSync(filename));

        this.checkCompatibleRuntime(serverless);

        // Override the variable resolver to declare our own variables
        const delegate = serverless.variables
            .getValueFromSource.bind(serverless.variables);
        serverless.variables.getValueFromSource = (variableString) => {
            if (variableString.startsWith('bref:layer.')) {
                const region = serverless.getProvider('aws').getRegion();
                const layerName = variableString.substr('bref:layer.'.length);
                if (! (layerName in layers)) {
                    throw `Unknown Bref layer named "${layerName}"`;
                }
                if (! (region in layers[layerName])) {
                    throw `There is no Bref layer named "${layerName}" in region "${region}"`;
                }
                const version = layers[layerName][region];
                return `arn:aws:lambda:${region}:209497400698:layer:${layerName}:${version}`;
            }

            return delegate(variableString);
        }
    }

    checkCompatibleRuntime(serverless) {
        if (serverless.service.provider.runtime === 'provided') {
            throw new Error('Bref 1.0 layers are not compatible with the "provided" runtime. To upgrade to Bref 1.0, you have to switch to "provided.al2" in serverless.yml. More details here: https://bref.sh/docs/news/01-bref-1.0.html#amazon-linux-2');
        }
        for (const [name, f] of Object.entries(serverless.service.functions)) {
            if (f.runtime === 'provided') {
                throw new Error(`Bref 1.0 layers are not compatible with the "provided" runtime. To upgrade to Bref 1.0, you have to switch to "provided.al2" in serverless.yml for the function "${name}". More details here: https://bref.sh/docs/news/01-bref-1.0.html#amazon-linux-2`);
            }
        }
    }
}

module.exports = ServerlessPlugin;

