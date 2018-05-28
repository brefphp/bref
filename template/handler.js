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

    let timeStartPhp = new Date().getTime();

    let script = spawn('php', ['bref.php', JSON.stringify(event)]);

    let scriptOutput = '';
    //dynamically collect output
    script.stdout.on('data', function(data) {
        console.log(data.toString());
        scriptOutput += data.toString()
    });
    //react to potential errors
    script.stderr.on('data', function(data) {
        console.log("STDERR: "+data.toString());
        scriptOutput += data.toString();
    });
    //finalize when process is done.
    script.on('close', function(code) {
        let result = null;
        if (fs.existsSync('/tmp/.bref/output.json')) {
            result = fs.readFileSync('/tmp/.bref/output.json', 'utf8');
            result = JSON.parse(result);
        }
        console.log('Exit code: ' + code + ', PHP run time: ' + ((new Date().getTime()) - timeStartPhp) + 'ms');
        if (code === 0) {
            callback(null, result);
        } else {
            callback(new Error('Exit code ' + code + ' - ' + scriptOutput));
        }
    });
};
