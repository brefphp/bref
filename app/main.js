process.env['PATH'] = process.env['PATH']
    + ':' + process.env['LAMBDA_TASK_ROOT'] + '/bin/php/bin'; // for PHP

// It seems to be necessary for lambci's PHP to work
// @see https://github.com/lambci/lambci/blob/c2a81276b2dcd70fae093f68cddcd42bf086ed8f/actions/build.js#L266-L269
process.env['LD_LIBRARY_PATH'] = process.env['LD_LIBRARY_PATH']
    + ':' + process.env['LAMBDA_TASK_ROOT'] + '/bin/usr/lib64';

const spawn = require('child_process').spawn;
const fs = require('fs');

exports.handler = function (event, context, callback) {
    let script = spawn('php', ['/var/task/lambda.php']);
    let output = '';
    //dynamically collect output
    script.stdout.on('data', function(data) {
        output+=data;
    });
    //react to potential errors
    script.stderr.on('data', function(data) {
        console.log("STDERR: "+data);
    });
    //finalize when process is done.
    script.on('close', function(code) {
        console.log(output);
        console.log('Exit code ' + code);
        if (code === 0) {
            callback(null, output);
        } else {
            callback(new Error(output));
        }
    });
};

