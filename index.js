'use strict';

const {listLayers} = require('./plugin/layers');
const {runConsole} = require('./plugin/run-console');
const {runLocal} = require('./plugin/local');
const fs = require('fs');
const path = require('path');

/**
 * This file declares a plugin for the Serverless framework.
 *
 * This lets us define variables and helpers to simplify creating PHP applications.
 */

class ServerlessPlugin {
    constructor(serverless, options, utils) {
        this.serverless = serverless;
        this.provider = this.serverless.getProvider('aws');

        if (!utils) {
            throw new serverless.classes.Error('Bref requires Serverless Framework v3, but an older v2 version is running.\nPlease upgrade to Serverless Framework v3.');
        }

        const filename = path.resolve(__dirname, 'layers.json');
        const layers = JSON.parse(fs.readFileSync(filename).toString());

        this.checkCompatibleRuntime();

        // Declare `${bref:xxx}` variables
        // See https://www.serverless.com/framework/docs/guides/plugins/custom-variables
        // noinspection JSUnusedGlobalSymbols
        this.configurationVariablesSources = {
            bref: {
                async resolve({address, resolveConfigurationProperty, options}) {
                    // `address` and `params` reflect values configured with a variable: ${bref(param1, param2):address}

                    // `options` is CLI options
                    // `resolveConfigurationProperty` allows to access other configuration properties,
                    // and guarantees to return a fully resolved form (even if property is configured with variables)
                    const region = options.region || await resolveConfigurationProperty(['provider', 'region']);

                    if (!address.startsWith('layer.')) {
                        throw new serverless.classes.Error(`Unknown Bref variable \${bref:${address}}, the only supported syntax right now is \${bref:layer.XXX}`);
                    }

                    const layerName = address.substring('layer.'.length);
                    if (! (layerName in layers)) {
                        throw new serverless.classes.Error(`Unknown Bref layer named "${layerName}".\nIs that a typo? Check out https://bref.sh/docs/runtimes/ to see the correct name of Bref layers.`);
                    }
                    if (! (region in layers[layerName])) {
                        throw new serverless.classes.Error(`There is no Bref layer named "${layerName}" in region "${region}".\nThat region may not be supported yet. Check out https://runtimes.bref.sh to see the list of supported regions.\nOpen an issue to ask for that region to be supported: https://github.com/brefphp/bref/issues`);
                    }
                    const version = layers[layerName][region];
                    return {
                        value: `arn:aws:lambda:${region}:534081306603:layer:${layerName}:${version}`,
                    }
                }
            }
        };

        this.commands = {
            'bref:cli': {
                usage: 'Runs a CLI command in AWS Lambda',
                lifecycleEvents: ['run'],
                options: {
                    // Define the '--args' option with the '-a' shortcut
                    command: {
                        usage: 'Specify the arguments/options of the command to run on AWS Lambda',
                        shortcut: 'a',
                        type: 'string',
                    },
                },
            },
            'bref:local': {
                usage: 'Runs a PHP Lambda function locally (better alternative to "serverless local")',
                lifecycleEvents: ['run'],
                options: {
                    function: {
                        usage: 'The name of the function to invoke',
                        shortcut: 'f',
                        required: true,
                        type: 'string',
                    },
                    data: {
                        usage: 'The data (as a JSON string) to pass to the handler',
                        shortcut: 'd',
                        type: 'string',
                    },
                    path: {
                        usage: 'Path to JSON or YAML file holding input data (use either this or --data)',
                        shortcut: 'p',
                        type: 'string',
                    },
                },
            },
            'bref:layers': {
                usage: 'Displays the versions of the Bref layers',
                lifecycleEvents: ['show'],
            },
        };

        // noinspection JSUnusedGlobalSymbols
        this.hooks = {
            // Custom commands
            'bref:cli:run': () => runConsole(this.serverless, options),
            'bref:local:run': () => runLocal(this.serverless, options),
            'bref:layers:show': () => listLayers(this.serverless, utils.log),
        };
    }

    checkCompatibleRuntime() {
        const errorMessage = 'Bref layers are not compatible with the "provided" runtime.\nYou have to use the "provided.al2" runtime instead in serverless.yml.\nMore details here: https://bref.sh/docs/news/01-bref-1.0.html#amazon-linux-2';
        if (this.serverless.service.provider.runtime === 'provided') {
            throw new this.serverless.classes.Error(errorMessage);
        }
        for (const [, f] of Object.entries(this.serverless.service.functions)) {
            if (f.runtime === 'provided') {
                throw new this.serverless.classes.Error(errorMessage);
            }
        }
    }
}

module.exports = ServerlessPlugin;
