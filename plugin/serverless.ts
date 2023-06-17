import type { AWS } from "@serverless/typescript";

export type Hook = () => void | Promise<void>;

export type VariableResolver = {
    /**
     * When using such expression in service file ${foo(param1, param2):address}, resolve will be invoked with the following values:
     *  - address: address
     *  - params: [param1, param2]
     *  - resolveConfigurationProperty: use to resolve other parts of the service file. Usage: `await resolveConfigurationProperty(["provider", "stage"])` will resolve provider.stage value
     *  - options: CLI options passed to the command
     */
    resolve: (context: {
        address: string;
        params: string[];
        resolveConfigurationProperty: (path: string[]) => Promise<string | Record<string, unknown>>;
        options: Record<string, string>;
    }) => { value: string | Record<string, unknown> } | Promise<{ value: string | Record<string, unknown> }>;
};

export type Provider = {
    naming: {
        getStackName: () => string;
        getLambdaLogicalId: (functionName: string) => string;
        getRestApiLogicalId: () => string;
        getHttpApiLogicalId: () => string;
        getCompiledTemplateFileName: () => string;
    };
    getRegion: () => string;
    /**
     * Send a request to the AWS API.
     */
    request: <Input, Output>(service: string, method: string, params: Input) => Promise<Output>;
};

export type Serverless = {
    serviceDir: string;
    pluginManager: {
        addPlugin: (plugin: unknown) => void;
        spawn: (command: string) => Promise<void>;
    };
    configSchemaHandler: {
        defineTopLevelProperty: (pluginName: string, schema: Record<string, unknown>) => void;
        schema: {
            definitions: {
                awsLambdaRuntime: {
                    enum: string[];
                };
            };
        };
    };
    configurationInput: AWS & {
        constructs?: Record<string, { type: string; provider?: string } & Record<string, any>>;
    };
    service: AWS;
    processedInput: {
        commands: string[];
        options: Record<string, unknown>;
    };
    getProvider: (provider: "aws") => Provider;
    addServiceOutputSection?(section: string, content: string | string[]): void;
    classes: {
        Error: new (message: string) => Error;
    };
};

export type CommandsDefinition = Record<
    string,
    {
        lifecycleEvents?: string[];
        commands?: CommandsDefinition;
        usage?: string;
        options?: {
            [name: string]: {
                usage: string;
                required?: boolean;
                shortcut?: string;
            };
        };
    }
>;

export type CliOptions = Record<string, string | boolean | string[]>;

export type ServerlessUtils = {
    writeText(message?: string | string[]): void;
    log: Logger;
    progress?: {
        create(opts?: { message: string }): Progress;
    };
};

export type Logger = ((message?: string) => void) & {
    debug(message?: string): void;
    verbose(message?: string): void;
    success(message?: string): void;
    warning(message?: string): void;
    error(message?: string): void;
    get(namespace: string): Logger;
};

export interface Progress {
    update(message?: string): void;
    remove(): void;
}
