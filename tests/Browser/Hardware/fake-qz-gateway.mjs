import assert from 'node:assert/strict';

export class FakeQzGateway {
    mode = 'ready';
    connections = 0;
    calls = [];
    prints = [];
    printers = [{ name: 'Office PDF QA', driver: 'Virtual PDF' }, { name: 'CT420D QA', driver: 'Virtual label driver' }];

    async install(context) {
        await context.routeWebSocket(/^wss:\/\/(localhost|127\.0\.0\.1):(?:8181|8282|8383|8484)\/?$/, (socket) => {
            this.connections++;
            socket.onMessage((raw) => {
                if (raw === 'ping') return;
                const message = JSON.parse(raw.toString());
                const reply = (result) => socket.send(JSON.stringify({ uid: message.uid, result }));
                this.calls.push(message.call ?? 'certificate');
                if (message.call === 'getVersion') return reply('2.3.0');
                if ('certificate' in message) return reply(null);
                if (message.call === 'printers.detail' || message.call === 'printers.details') {
                    if (this.mode === 'denied') return socket.send(JSON.stringify({ uid: message.uid, error: 'Permission denied by QA' }));
                    return reply(this.mode === 'empty' ? [] : this.printers);
                }
                if (message.call === 'print') {
                    const { data, printer, options } = message.params;
                    assert.equal(data.length, 1);
                    assert.equal(data[0].type, 'pixel');
                    assert.equal(data[0].format, 'image');
                    assert.equal(data[0].flavor, 'base64');
                    assert.match(data[0].data, /^iVBOR/);
                    this.prints.push({ printer, options, pngBytes: Buffer.from(data[0].data, 'base64').length });
                    if (this.mode === 'lost_ack') { socket.close({ code: 1001, reason: 'QA connection lost after send' }); return; }
                    return reply(null);
                }
                throw new Error('Unexpected QZ API: ' + message.call);
            });
        });
    }
}
