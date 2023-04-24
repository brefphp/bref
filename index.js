'use strict';

const {listLayers} = require('./plugin/layers');
const {runConsole} = require('./plugin/run-console');
const {runLocal} = require('./plugin/local');
const {warnIfUsingSecretsWithoutTheBrefDependency} = require('./plugin/secrets');
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
        this.utils = utils;

        // Automatically enable faster deployments (unless a value is already set)
        // https://www.serverless.com/framework/docs/providers/aws/guide/deploying#deployment-method
        if (! serverless.service.provider.deploymentMethod) {
            serverless.service.provider.deploymentMethod = 'direct';
        }

        const filename = path.resolve(__dirname, 'layers.json');
        this.layers = JSON.parse(fs.readFileSync(filename).toString());

        this.runtimes = Object.keys(this.layers)
            .filter(name => !name.startsWith('arm-'));
        // Console runtimes must have a PHP version provided
        this.runtimes = this.runtimes.filter(name => name !== 'console');
        this.runtimes.push('php-80-console', 'php-81-console', 'php-82-console');

        this.checkCompatibleRuntime();

        serverless.configSchemaHandler.schema.definitions.awsLambdaRuntime.enum.push(...this.runtimes);

        // Declare `${bref:xxx}` variables
        // See https://www.serverless.com/framework/docs/guides/plugins/custom-variables
        // noinspection JSUnusedGlobalSymbols
        this.configurationVariablesSources = {
            bref: {
                resolve: async ({address, resolveConfigurationProperty, options}) => {
                    // `address` and `params` reflect values configured with a variable: ${bref(param1, param2):address}

                    // `options` is CLI options
                    // `resolveConfigurationProperty` allows to access other configuration properties,
                    // and guarantees to return a fully resolved form (even if property is configured with variables)
                    const region = options.region || await resolveConfigurationProperty(['provider', 'region']);

                    if (!address.startsWith('layer.')) {
                        throw new serverless.classes.Error(`Unknown Bref variable \${bref:${address}}, the only supported syntax right now is \${bref:layer.XXX}`);
                    }

                    const layerName = address.substring('layer.'.length);
                    return {
                        value: this.getLayerArn(layerName, region),
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
                    args: {
                        usage: 'Specify the arguments/options of the command to run on AWS Lambda',
                        shortcut: 'a',
                        type: 'string',
                    },
                    function: {
                        usage: 'The name of the function to invoke (optional, auto-discovered by default)',
                        shortcut: 'f',
                        required: false,
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
            'initialize': () => {
                this.processPhpRuntimes();
                warnIfUsingSecretsWithoutTheBrefDependency(this.serverless, utils.log);
                try {
                    this.telemetry();
                } catch (e) {
                    // These errors should not stop the execution
                    this.utils.log.verbose(`Could not send telemetry: ${e}`);
                }
            },
            // Custom commands
            'bref:cli:run': () => runConsole(this.serverless, options),
            'bref:local:run': () => runLocal(this.serverless, options),
            'bref:layers:show': () => listLayers(this.serverless, utils.log),
        };
    }

    /**
     * Process the `php-xx` runtimes to turn them into `provided.al2` runtimes + Bref layers.
     */
    processPhpRuntimes() {
        const includeBrefLayers = (runtime, existingLayers, isArm) => {
            let layerName = runtime;
            // Automatically use ARM layers if the function is deployed to an ARM architecture
            if (isArm) {
                layerName = 'arm-' + layerName;
            }
            if (layerName.endsWith('-console')) {
                layerName = layerName.substring(0, layerName.length - '-console'.length);
                existingLayers.unshift(this.getLayerArn('console', this.provider.getRegion()));
                existingLayers.unshift(this.getLayerArn(layerName, this.provider.getRegion()));
            } else {
                existingLayers.unshift(this.getLayerArn(layerName, this.provider.getRegion()));
            }
            return existingLayers;
        }

        const config = this.serverless.service;
        const isArmGlobally = config.provider.architecture === 'arm64';

        // Check provider config
        if (this.runtimes.includes(config.provider.runtime || '')) {
            config.provider.layers = includeBrefLayers(
                config.provider.runtime,
                config.provider.layers || [], // make sure it's an array
                isArmGlobally,
            );
            config.provider.runtime = 'provided.al2';
        }

        // Check functions config
        for (const f of Object.values(config.functions || {})) {
            if (this.runtimes.includes(f.runtime)) {
                f.layers = includeBrefLayers(
                    f.runtime,
                    f.layers || [], // make sure it's an array
                    f.architecture === 'arm64' || (isArmGlobally && !f.architecture),
                );
                f.runtime = 'provided.al2';
            }
        }

        // Check Lift constructs config
        for (const construct of Object.values(this.serverless.configurationInput.constructs || {})) {
            if (construct.type !== 'queue' && construct.type !== 'webhook') continue;
            const f = construct.type === 'queue' ? construct.worker : construct.authorizer;
            if (f && this.runtimes.includes(f.runtime)) {
                f.layers = includeBrefLayers(
                    f.runtime,
                    f.layers || [], // make sure it's an array
                    f.architecture === 'arm64' || (isArmGlobally && !f.architecture),
                );
                f.runtime = 'provided.al2';
            }
        }
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

    getLayerArn(layerName, region) {
        if (! (layerName in this.layers)) {
            throw new this.serverless.classes.Error(`Unknown Bref layer named "${layerName}".\nIs that a typo? Check out https://bref.sh/docs/runtimes/ to see the correct name of Bref layers.`);
        }
        if (! (region in this.layers[layerName])) {
            throw new this.serverless.classes.Error(`There is no Bref layer named "${layerName}" in region "${region}".\nThat region may not be supported yet. Check out https://runtimes.bref.sh to see the list of supported regions.\nOpen an issue to ask for that region to be supported: https://github.com/brefphp/bref/issues`);
        }
        const version = this.layers[layerName][region];
        return `arn:aws:lambda:${region}:534081306603:layer:${layerName}:${version}`;
    }

    /**
     * Bref telemetry to estimate the number of users and which commands are most used.
     *
     * The data sent is anonymous, and sent over UDP.
     * Unlike TCP, UDP does not check that the message correctly arrived to the server.
     * It doesn't even establish a connection: the data is sent over the network and the code moves on to the next line.
     * That means that UDP is extremely fast (150 micro-seconds) and will not impact the CLI.
     * It can be disabled by setting the `SLS_TELEMETRY_DISABLED` environment variable to `1`.
     *
     * About UDP: https://en.wikipedia.org/wiki/User_Datagram_Protocol
     */
    telemetry() {
        // Respect the native env variable
        if (process.env.SLS_TELEMETRY_DISABLED) {
            return;
        }

        const userConfig = require.main.require('@serverless/utils/config');
        const ci = require.main.require('ci-info');

        let command = 'unknown';
        if (this.serverless.processedInput && this.serverless.processedInput.commands) {
            command = this.serverless.processedInput.commands.join(' ');
        }

        const payload = {
            cli: 'sls',
            v: 2, // Bref version
            c: command,
            ci: ci.isCI,
            install: userConfig.get('meta.created_at'),
            uid: userConfig.get('frameworkId'), // anonymous user ID created by the Serverless Framework
        };
        /** @type {Record<string, any>} */
        const config = this.serverless.configurationInput;
        const plugins = this.serverless.service.plugins ? this.serverless.service.plugins.modules || this.serverless.service.plugins : [];
        // Lift construct types
        if (plugins.includes('serverless-lift') && typeof config.constructs === 'object') {
            payload.constructs = Object.values(config.constructs)
                .map((construct) => (typeof construct === 'object' && construct.type) ? construct.type : null)
                .filter(Boolean);
        }

        // Send as a UDP packet to 108.128.197.71:8888
        const dgram = require('dgram');
        const client = dgram.createSocket('udp4');
        // This IP address is the Bref server.
        // If this server is down or unreachable, there should be no difference in overhead
        // or execution time.
        client.send(JSON.stringify(payload), 8888, '108.128.197.71', (err) => {
            if (err) {
                // These errors should not stop the execution
                this.utils.log.verbose(`Could not send telemetry: ${err.message}`);
            }
            try {
                client.close();
            } catch (e) {
                // These errors should not stop the execution
            }
        });
    }
}

module.exports = ServerlessPlugin;
