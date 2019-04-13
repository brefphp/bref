'use strict';

class ServerlessPlugin {
    constructor(serverless, options) {
        const delegate = serverless.variables
            .getValueFromSource.bind(serverless.variables);

        serverless.variables.getValueFromSource = (variableString) => {
            if (variableString === 'bref:layer.php-72') {
                return 'arn:aws:lambda:us-east-2:209497400698:layer:php-72:3';
            }

            return delegate(variableString);
        }
    }
}

module.exports = ServerlessPlugin;
