'use strict';

const {listLayers} = require('./plugin/layers');
const {runConsole} = require('./plugin/run-console');
const {runLocal} = require('./plugin/local');
const {warnIfUsingSecretsWithoutTheBrefDependency} = require('./plugin/secrets');
const fs = require('fs');
const path = require('path');

// Disable `sls` promoting the Serverless Console because it's not compatible with PHP, it's tripping users up
if (!process.env.SLS_NOTIFICATIONS_MODE) {
    process.env.SLS_NOTIFICATIONS_MODE = 'upgrades-only';
}

/**
 * This file declares a plugin for the Serverless framework.
 *
 * This lets us define variables and helpers to simplify creating PHP applications.
 */

class ServerlessPlugin {
    /**
     * @param {import('./plugin/serverless').Serverless} serverless
     * @param {import('./plugin/serverless').CliOptions} options
     * @param {import('./plugin/serverless').ServerlessUtils} utils
     */
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
        /** @type {Record<string, Record<string, string>>} */
        this.layers = JSON.parse(fs.readFileSync(filename).toString());

        this.runtimes = Object.keys(this.layers)
            .filter(name => !name.startsWith('arm-'));
        // Console runtimes must have a PHP version provided
        this.runtimes = this.runtimes.filter(name => name !== 'console');
        this.runtimes.push('php-80-console', 'php-81-console', 'php-82-console', 'php-83-console');

        this.checkCompatibleRuntime();

        serverless.configSchemaHandler.schema.definitions.awsLambdaRuntime.enum.push(...this.runtimes);

        // Declare `${bref:xxx}` variables
        // See https://www.serverless.com/framework/docs/guides/plugins/custom-variables
        // noinspection JSUnusedGlobalSymbols
        /** @type {Record<string, import('./plugin/serverless').VariableResolver>} */
        this.configurationVariablesSources = {
            bref: {
                resolve: async ({address, resolveConfigurationProperty, options}) => {
                    // `address` and `params` reflect values configured with a variable: ${bref(param1, param2):address}

                    // `options` is CLI options
                    // `resolveConfigurationProperty` allows to access other configuration properties,
                    // and guarantees to return a fully resolved form (even if property is configured with variables)
                    /** @type {string} */
                    // @ts-ignore
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

        /** @type {import('./plugin/serverless').CommandsDefinition} */
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
        /** @type {import('./plugin/serverless').HooksDefinition} */
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
            'before:logs:logs': () => {
                /** @type {typeof import('chalk')} */
                // @ts-ignore
                const chalk = require.main.require('chalk');
                utils.log(chalk.gray('View, tail, and search logs from all functions with https://dashboard.bref.sh'));
                utils.log();
            },
            'before:metrics:metrics': () => {
                /** @type {typeof import('chalk')} */
                // @ts-ignore
                const chalk = require.main.require('chalk');
                utils.log(chalk.gray('View all your application\'s metrics with https://dashboard.bref.sh'));
                utils.log();
            },
        };

        process.on('beforeExit', (code) => {
            const command = serverless.processedInput.commands[0] || '';
            // On successful deploy
            if (command.startsWith('deploy') && code === 0) {
                /** @type {typeof import('chalk')} */
                // @ts-ignore
                const chalk = require.main.require('chalk');
                utils.log();
                utils.log(chalk.gray('Want a better experience than the AWS console? Try out https://dashboard.bref.sh'));
            }
        });
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
        const isBrefRuntimeGlobally = this.runtimes.includes(config.provider.runtime || '');

        // Check functions config
        for (const f of Object.values(config.functions || {})) {
            if (
              (f.runtime && this.runtimes.includes(f.runtime)) ||
              (!f.runtime && isBrefRuntimeGlobally)
            ) {
                // The logic here is a bit custom:
                // If there are layers on the function, we preserve them
                let existingLayers = f.layers || []; // make sure it's an array
                // Else, we merge with the layers defined at the root.
                // Indeed, SF overrides the layers defined at the root with the ones defined on the function.
                if (existingLayers.length === 0) {
                    // for some reason it's not always an array
                    existingLayers = Array.from(config.provider.layers || []);
                }

                f.layers = includeBrefLayers(
                    f.runtime || config.provider.runtime,
                    existingLayers,
                    f.architecture === 'arm64' || (isArmGlobally && !f.architecture),
                );
                f.runtime = 'provided.al2';
            }
        }

        // Check Lift constructs config
        for (const construct of Object.values(this.serverless.configurationInput.constructs || {})) {
            if (construct.type !== 'queue' && construct.type !== 'webhook') continue;
            const f = construct.type === 'queue' ? construct.worker : construct.authorizer;
            if (f && (f.runtime && this.runtimes.includes(f.runtime) || !f.runtime && isBrefRuntimeGlobally) ) {
                f.layers = includeBrefLayers(
                    f.runtime || config.provider.runtime,
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
        for (const [, f] of Object.entries(this.serverless.service.functions || {})) {
            if (f.runtime === 'provided') {
                throw new this.serverless.classes.Error(errorMessage);
            }
        }
    }

    /**
     * @param {string} layerName
     * @param {string} region
     * @returns {string}
     */
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

        /** @type {{ get: (string) => string }} */
        // @ts-ignore
        const userConfig = require.main.require('@serverless/utils/config');
        /** @type {typeof import('ci-info')} */
        // @ts-ignore
        const ci = require.main.require('ci-info');

        let command = 'unknown';
        if (this.serverless.processedInput && this.serverless.processedInput.commands) {
            command = this.serverless.processedInput.commands.join(' ');
        }

        let timezone;
        try {
            timezone = new Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch {
            // Pass silently
        }

        const payload = {
            cli: 'sls',
            v: 2, // Bref version
            c: command,
            ci: ci.isCI,
            install: userConfig.get('meta.created_at'),
            uid: userConfig.get('frameworkId'), // anonymous user ID created by the Serverless Framework
            tz: timezone,
        };
        const config = this.serverless.configurationInput;
        /** @type {string[]} */
        let plugins = [];
        if (this.serverless.service.plugins && 'modules' in this.serverless.service.plugins) {
            plugins = this.serverless.service.plugins.modules;
        } else if (this.serverless.service.plugins) {
            plugins = this.serverless.service.plugins;
        }
        // Lift construct types
        if (plugins.includes('serverless-lift') && typeof config.constructs === 'object') {
            payload.constructs = Object.values(config.constructs)
                .map((construct) => (typeof construct === 'object' && construct.type) ? construct.type : null)
                .filter(Boolean);
        }

        // PHP extensions
        const extensionLayers = [];
        const allLayers = [];
        if (config.provider && config.provider.layers && Array.isArray(config.provider.layers)) {
            allLayers.push(...config.provider.layers);
        }
        Object.values(config.functions || {}).forEach((f) => {
            if (f.layers && Array.isArray(f.layers)) {
                allLayers.push(...f.layers);
            }
        });
        if (allLayers.length > 0) {
            const layerRegex = /^arn:aws:lambda:[^:]+:403367587399:layer:([^:]+)-php-[^:]+:[^:]+$/;
            /** @type {string[]} */
            // @ts-ignore
            const extensionLayerArns = allLayers
                .filter((layer) => {
                    return typeof layer === 'string'
                        && layer.includes('403367587399');
                });
            for (const layer of extensionLayerArns) {
                // Extract the layer name from the ARN.
                // The ARN looks like this: arn:aws:lambda:us-east-2:403367587399:layer:amqp-php-81:12
                const match = layer.match(layerRegex);
                if (match && match[1] && ! extensionLayers.includes(match[1])) {
                    extensionLayers.push(match[1]);
                }
            }
        }
        if (extensionLayers.length > 0) {
            payload.ext = extensionLayers;
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
