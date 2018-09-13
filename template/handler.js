if (process.env['LAMBDA_TASK_ROOT']) {
    // add bin directory to PATH to run PHP binary
    process.env['PATH'] = process.env['PATH']
        + ':' + process.env['LAMBDA_TASK_ROOT'] + '/.bref/bin';

    // add lib directory to LD_LIBRARY_PATH to make PHP binary able to load required libraries
    process.env['LD_LIBRARY_PATH'] = process.env['LD_LIBRARY_PATH']
        + ':' + process.env['LAMBDA_TASK_ROOT'] + '/.bref/bin/lib';
}

const spawn = require('child_process').spawn;
const fs = require('fs');

const TMP_DIRECTORY = process.env['TMP_DIRECTORY'] ? process.env['TMP_DIRECTORY'] : '/tmp/.bref';
const OUTPUT_FILE = TMP_DIRECTORY + '/output.json';
const PHP_FILE = process.env['PHP_HANDLER'] ? process.env['PHP_HANDLER'] : 'bref.php';

/**
 * This is the JavaScript lambda that is invoked by AWS
 */
exports.handle = function(event, context, callback) {
    if (fs.existsSync(OUTPUT_FILE)) {
        fs.unlinkSync(OUTPUT_FILE);
    } else if (!fs.existsSync(TMP_DIRECTORY)) {
        fs.mkdirSync(TMP_DIRECTORY);
    }

    // Ensure the directory for storing the opcache file exists else PHP crashes
    if (!fs.existsSync(TMP_DIRECTORY + '/opcache')) {
        fs.mkdirSync(TMP_DIRECTORY + '/opcache');
    }

    // Execute bref.php and pass the event as argument
    let phpParameters = [PHP_FILE, JSON.stringify(event)];
    if (!process.env['BREF_LOCAL']) {
        // Override php.ini but only when running in production
        phpParameters.unshift('--php-ini=.bref/php.ini');
    }
    let script = spawn('php', phpParameters);

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
        if (fs.existsSync(OUTPUT_FILE)) {
            result = fs.readFileSync(OUTPUT_FILE, 'utf8');
            result = JSON.parse(result);
        }
        if (code === 0) {
            callback(null, result);
        } else {
            callback(new Error('PHP exit code: ' + code));
        }
    });
};
