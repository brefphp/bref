'use strict';

const {runConsole} = require('./plugin/run-console');
const {runLocal} = require('./plugin/local');
const {warnIfUsingSecretsWithoutTheBrefDependency} = require('./plugin/secrets');

const ciEnvironmentVariables = [
    'BUILD_ID', // Jenkins, Cloudbees
    'BUILD_NUMBER', // Jenkins, TeamCity
    'CI', // Travis CI, CircleCI, GitLab CI, etc.
    'CI_APP_ID', // Appflow
    'CI_BUILD_ID', // Appflow
    'CI_BUILD_NUMBER', // Appflow
    'CI_NAME', // Codeship and others
    'CONTINUOUS_INTEGRATION', // Travis CI, Cirrus CI
    'RUN_ID', // TaskCluster, dsari
    'GITHUB_ACTIONS',
    'GITLAB_CI',
    'CIRCLECI',
    'TRAVIS',
    'APPVEYOR',
    'BUILDKITE',
    'CODEBUILD_BUILD_ARN',
    'TF_BUILD', // Azure Pipelines
    'TEAMCITY_VERSION',
    'JENKINS_URL',
    'BITBUCKET_COMMIT',
    'DRONE',
    'NETLIFY',
    'VERCEL',
    'CF_PAGES', // Cloudflare Pages
    'RENDER',
    'BITRISE_IO',
];

// Bref runtimes: customer-managed Lambda runtimes published by Bref, one per PHP version (for x86 and ARM)
const runtimePhpVersions = ['83', '84', '85', '86'];
const runtimeAlias = '4';
const runtimeRegions = [
    'af-south-1',
    'ap-east-1',
    'ap-northeast-1',
    'ap-northeast-2',
    'ap-northeast-3',
    'ap-south-1',
    'ap-southeast-1',
    'ap-southeast-2',
    'ap-southeast-3',
    'ca-central-1',
    'eu-central-1',
    'eu-north-1',
    'eu-south-1',
    'eu-south-2',
    'eu-west-1',
    'eu-west-2',
    'eu-west-3',
    'sa-east-1',
    'us-east-1',
    'us-east-2',
    'us-west-1',
    'us-west-2',
];

function isCiEnvironment(env) {
    return env.CI !== 'false' && ciEnvironmentVariables.some((name) => Boolean(env[name]));
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
        this.utils = utils;

        this.checkOslsVersion();

        // Automatically enable faster deployments (unless a value is already set)
        // https://www.serverless.com/framework/docs/providers/aws/guide/deploying#deployment-method
        if (! serverless.service.provider.deploymentMethod) {
            serverless.service.provider.deploymentMethod = 'direct';
        }

        serverless.configSchemaHandler.schema.definitions.awsLambdaRuntime.enum.push(
            ...runtimePhpVersions.flatMap((v) => [`php-${v}`, `php-${v}-fpm`, `php-${v}-console`]),
        );
        serverless.configSchemaHandler.defineTopLevelProperty('bref', {
            type: 'object',
        });

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
                        usage: 'Path to JSON file holding input data (use either this or --data)',
                        shortcut: 'p',
                        type: 'string',
                    },
                },
            },
        };

        // noinspection JSUnusedGlobalSymbols
        /** @type {import('./plugin/serverless').HooksDefinition} */
        this.hooks = {
            'initialize': () => {
                this.processPhpRuntimes();
                this.exposeStackName();
                this.configureResponseStreaming();
                this.disableConsoleRetries();
                warnIfUsingSecretsWithoutTheBrefDependency(this.serverless, utils.log);
                try {
                    this.telemetry();
                } catch (e) {
                    // These errors should not stop the execution
                    this.utils.log.verbose(`Could not send telemetry: ${e}`);
                }
            },
            // Custom commands
            'bref:cli:run': () => runConsole(this.serverless, options, utils.log),
            'bref:local:run': () => runLocal(this.serverless, options),
            'before:logs:logs': () => {
                utils.log(this.gray('View, tail, and search logs from all functions with https://bref.sh/cloud'));
                utils.log();
            },
            'before:metrics:metrics': () => {
                utils.log(this.gray('View all your application\'s metrics with https://bref.sh/cloud'));
                utils.log();
            },
        };

        process.on('beforeExit', (code) => {
            const command = serverless.processedInput.commands[0] || '';
            // On successful deploy
            if (command.startsWith('deploy') && code === 0) {
                utils.log();
                utils.log(this.gray('Want a better experience than the AWS console? Try out https://bref.sh/cloud'));
            }
        });
    }

    /**
     * Process the `php-xx` runtimes to turn them into Bref's runtimes (customer-managed Lambda runtimes).
     */
    processPhpRuntimes() {
        /**
         * @param {string} runtime
         * @return {string|undefined}
         */
        const runtimeStringToRuntimeClass = (runtime) => {
            if (! runtime.startsWith('php-')) {
                return undefined;
            }
            if (runtime.endsWith('-console')) {
                return 'Bref\\ConsoleRuntime\\Main';
            }
            if (runtime.endsWith('-fpm')) {
                return 'Bref\\FpmRuntime\\Main';
            }
            return 'Bref\\FunctionRuntime\\Main';
        };
        const configureFunctionRuntime = (f) => {
            // `php-\d\d(-fpm|console)?`
            const fullRuntimeString = f.runtime || config.provider.runtime;
            if (! fullRuntimeString || ! fullRuntimeString.startsWith('php-')) {
                return;
            }
            const phpVersion = fullRuntimeString.substring('php-'.length).split('-')[0];
            const runtimeClass = runtimeStringToRuntimeClass(fullRuntimeString);
            if (! runtimeClass) return;

            // The same runtime works on x86 and ARM
            f.runtime = this.getRuntimeArn(phpVersion, this.provider.getRegion());
            // Add the `BREF_RUNTIME` environment variable
            // to let the function know which runtime it is using
            // (this is used by the Bref runtime)
            if (!f.environment) {
                f.environment = {};
            }
            if (!f.environment.BREF_RUNTIME) {
                f.environment.BREF_RUNTIME = runtimeClass;
            }
        }

        const config = this.serverless.service;

        // Check functions config
        for (const f of Object.values(config.functions || {})) {
            configureFunctionRuntime(f);
        }

        // Check Lift constructs config
        for (const construct of Object.values(this.serverless.configurationInput.constructs || {})) {
            if (construct.type !== 'queue' && construct.type !== 'webhook') continue;
            const f = construct.type === 'queue' ? construct.worker : construct.authorizer;
            if (f) {
                configureFunctionRuntime(f);
            }
        }
    }

    /**
     * Set the `BREF_STACK_NAME` environment variable on all functions (including Docker images and Lift workers).
     */
    exposeStackName() {
        const provider = this.serverless.service.provider;
        if (!provider.environment) {
            provider.environment = {};
        }
        if (!provider.environment.BREF_STACK_NAME) {
            provider.environment.BREF_STACK_NAME = this.provider.naming.getStackName();
        }
    }

    /**
     * Set the `BREF_RESPONSE_STREAMING` environment variable on functions whose URL or REST API streams:
     * Bref cannot tell from the events, and each mode breaks with the other's response format.
     */
    configureResponseStreaming() {
        for (const f of Object.values(this.serverless.service.functions || {})) {
            const streamedUrl = typeof f.url === 'object' && f.url.invokeMode === 'RESPONSE_STREAM';
            const streamedRestApi = (f.events || []).some((event) => {
                // `response.transferMode` is not in the types of the `serverless` package
                const http = 'http' in event ? /** @type {any} */ (event.http) : undefined;
                return typeof http === 'object' && String((http.response || {}).transferMode).toUpperCase() === 'STREAM';
            });
            if (!streamedUrl && !streamedRestApi) continue;
            if (!f.environment) {
                f.environment = {};
            }
            if (f.environment.BREF_RESPONSE_STREAMING === undefined) {
                f.environment.BREF_RESPONSE_STREAMING = '1';
            }
        }
    }

    /**
     * Lambda does not retry failed console commands invoked asynchronously (e.g. crons), unless configured otherwise.
     */
    disableConsoleRetries() {
        for (const f of Object.values(this.serverless.service.functions || {})) {
            const brefRuntime = (f.environment || {}).BREF_RUNTIME;
            if (brefRuntime !== 'Bref\\ConsoleRuntime\\Main' && brefRuntime !== 'console') continue;
            if (f.maximumRetryAttempts === undefined) {
                f.maximumRetryAttempts = 0;
            }
        }
    }

    /**
     * Bref v4 deploys functions on customer-managed runtimes, supported by osls since 4.8.
     * The Serverless Framework (v3, or Serverless Inc's v4) is not supported.
     */
    checkOslsVersion() {
        let isOsls = false;
        try {
            // `require.main` is osls' `bin/serverless.js`
            const main = require.main;
            isOsls = main !== undefined && main.require('../package.json').name === 'osls';
        } catch {
            // Not osls
        }
        const [major, minor] = String(this.serverless.version).split('.').map(Number);
        if (isOsls && (major > 4 || (major === 4 && minor >= 8))) return;
        const running = isOsls ? `osls ${this.serverless.version}` : `Serverless Framework ${this.serverless.version}`;
        throw new this.serverless.classes.Error(`Bref v4 requires osls 4.8 or greater (https://github.com/oss-serverless/osls), but ${running} is running.\nInstall it with "npm install -g osls@4".`);
    }

    /**
     * @param {string} phpVersion
     * @param {string} region
     * @returns {string}
     */
    getRuntimeArn(phpVersion, region) {
        if (! runtimePhpVersions.includes(phpVersion)) {
            throw new this.serverless.classes.Error(`Unknown Bref runtime "php-${phpVersion}".\nBref v4 supports these PHP versions: ${runtimePhpVersions.map((v) => `php-${v}`).join(', ')}.`);
        }
        if (! runtimeRegions.includes(region)) {
            throw new this.serverless.classes.Error(`Bref runtimes are not available in region "${region}".\nOpen an issue to ask for that region to be supported: https://github.com/brefphp/bref/issues`);
        }
        return `arn:aws:lambda:${region}:873528684822:runtime/php-${phpVersion}/alias/${runtimeAlias}`;
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
            v: 4, // Bref version
            c: command,
            ci: isCiEnvironment(process.env),
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

    /**
     * @param {string} text
     * @returns {string}
     */
    gray(text) {
        const grayText = "\x1b[90m";
        const reset = "\x1b[0m";
        return `${grayText}${text}${reset}`;
    }
}

module.exports = ServerlessPlugin;
