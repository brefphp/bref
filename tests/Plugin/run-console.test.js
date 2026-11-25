const {test} = require('node:test');
const assert = require('node:assert');
const {commandEvent, streamCommand} = require('../../plugin/run-console');

/**
 * A Lambda client that answers InvokeWithResponseStream with these events.
 *
 * @param {any[]} events
 */
function lambdaReturning(events) {
    return {
        send: async () => ({
            EventStream: (async function* () {
                yield* events;
            })(),
        }),
    };
}

test('the event asks the console runtime to stream the output', () => {
    assert.deepStrictEqual(commandEvent('migrate --force'), {cli: 'migrate --force', stream: true});
    assert.deepStrictEqual(commandEvent(undefined), {cli: '', stream: true});
});

test('the output is written as it is received', async () => {
    const written = [];
    const error = await streamCommand(lambdaReturning([
        {PayloadChunk: {Payload: Buffer.from('Hello ')}},
        {PayloadChunk: {Payload: Buffer.from('world')}},
        {InvokeComplete: {}},
    ]), {}, (chunk) => written.push(chunk.toString()));

    assert.deepStrictEqual(written, ['Hello ', 'world']);
    assert.strictEqual(error, undefined);
});

test('a failed command returns the error message of the runtime', async () => {
    const written = [];
    const error = await streamCommand(lambdaReturning([
        {PayloadChunk: {Payload: Buffer.from('Some output')}},
        {InvokeComplete: {
            ErrorCode: 'Bref\\ConsoleRuntime\\CommandFailed',
            ErrorDetails: JSON.stringify({errorType: 'Bref\\ConsoleRuntime\\CommandFailed', errorMessage: 'The command exited with code 2'}),
        }},
    ]), {}, (chunk) => written.push(chunk.toString()));

    assert.deepStrictEqual(written, ['Some output']);
    assert.strictEqual(error, 'The command exited with code 2');
});

test('an error without details returns its code', async () => {
    const error = await streamCommand(lambdaReturning([
        {InvokeComplete: {ErrorCode: 'Unhandled'}},
    ]), {}, () => {});

    assert.strictEqual(error, 'Unhandled');
});

test('the stream is read to its end after an error', async () => {
    let read = false;
    const lambda = {
        send: async () => ({
            EventStream: (async function* () {
                yield {InvokeComplete: {ErrorCode: 'Unhandled'}};
                // Else the connection stays open and the command never exits
                read = true;
            })(),
        }),
    };

    await streamCommand(lambda, {}, () => {});

    assert.strictEqual(read, true);
});
