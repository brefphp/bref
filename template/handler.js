process.env['PATH'] = process.env['PATH']
    + ':' + process.env['LAMBDA_TASK_ROOT'] + '/.bref/bin'; // for PHP

const spawn = require('child_process').spawn;
const fs = require('fs');

exports.handle = function(event, context, callback) {
    let recursiveRmDir = function(path) {
        let files = [];
        if (fs.existsSync(path)) {
            files = fs.readdirSync(path);
            files.forEach(function(file) {
                const curPath = path + "/" + file;
                if (fs.lstatSync(curPath).isDirectory()) { // recurse
                    recursiveRmDir(curPath);
                } else { // delete file
                    fs.unlinkSync(curPath);
                }
            });
            fs.rmdirSync(path);
        }
    };

    // Write the event to file
    if (fs.existsSync('/tmp/.bref')) {
        recursiveRmDir('/tmp/.bref');
    }
    fs.mkdirSync('/tmp/.bref');

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
        console.log('Exit code: ' + code);
        if (code === 0) {
            console.log('Result payload: ' + JSON.stringify(result));
            callback(null, result);
        } else {
            callback(new Error('Exit code ' + code + ' - ' + scriptOutput));
        }
    });
};
