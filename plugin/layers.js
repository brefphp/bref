const fs = require('fs');
const path = require('path');

function listLayers(serverless, log) {
    const region = serverless.getProvider("aws").getRegion();

    const json = fs.readFileSync(path.join(__dirname, '../layers.json'));
    const layers = JSON.parse(json.toString());
    log(`Layers for the ${region} region:`);

    log();
    log('Layer        Version   ARN');
    log('----------------------------------------------------------------------------------');
    for (const [layer, versions] of Object.entries(layers)) {
        const version = versions[region];
        const arn = `arn:aws:lambda:${region}:534081306603:layer:${layer}:${version}`;
        log(`${padString(layer, 12)} ${padString(version, 9)} ${arn}`);
    }
}

function padString(str, length) {
    return str.padEnd(length, ' ');
}

module.exports = {listLayers};
