process.env['PATH'] = process.env['PATH']
    + ':' + process.env['LAMBDA_TASK_ROOT'] + '/.bref/bin'; // for PHP

const spawn = require('child_process').spawn;
const fs = require('fs');

exports.handle = function(event, context, callback) {
    if (fs.existsSync('/tmp/.bref/output.json')) {
        fs.unlinkSync('/tmp/.bref/output.json');
    } else if (!fs.existsSync('/tmp/.bref')) {
        fs.mkdirSync('/tmp/.bref');
    }

    let script = spawn('php', ['bref.php', JSON.stringify(event)]);

    // PHP's output is passed to the lambda's logs
    script.stdout.on('data', function(data) {
        console.log(data.toString());
    });
    // PHP's error output is also passed to the lambda's logs
    script.stderr.on('data', function(data) {
        console.log('[STDERR] ' + data.toString());
    });

    script.on('close', function(code) {
        let result = null;
        // Read PHP's output
        if (fs.existsSync('/tmp/.bref/output.json')) {
            result = fs.readFileSync('/tmp/.bref/output.json', 'utf8');
            result = JSON.parse(result);
        }
        if (code === 0) {
            callback(null, result);
        } else {
            callback(new Error('PHP exit code: ' + code));
        }
    });
};
