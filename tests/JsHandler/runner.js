const handler = require('../../template/handler');
const fs = require('fs');

handler.handle([], [], function(error, response) {
    fs.writeFileSync('tmp/testError.json', JSON.stringify(error));
    fs.writeFileSync('tmp/testResponse.json', JSON.stringify(response));
});
