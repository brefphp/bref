const handler = require('../../template/handler');
const fs = require('fs');

const event = JSON.parse(process.argv[2]);

handler.handle(event, [], function(error, response) {
    fs.writeFileSync('tmp/testError.json', JSON.stringify(error));
    fs.writeFileSync('tmp/testResponse.json', JSON.stringify(response));
});
