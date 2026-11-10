const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const elements = new Map();
const messages = [], requests = [], added = [];
function element(id) {
    if (!elements.has(id)) elements.set(id, {
        value: '', style: {}, textContent: '', handlers: {},
        addEventListener(name, handler) { this.handlers[name] = handler; }
    });
    return elements.get(id);
}
let nextResponse;
const context = vm.createContext({
    console, document: { getElementById: element, addEventListener() {} },
    fetch(url) { requests.push(url); return nextResponse(); },
    setTimeout() {}, clearTimeout() {}, window: {},
    showToast(message, type) { messages.push({ message, type }); }
});
vm.runInContext(fs.readFileSync(path.join(root, 'assets/js/pos.js'), 'utf8'), context);
context.addToCart = item => added.push(item);
let focused = 0;
context.focusScanner = () => focused++;
context.bindEvents();
function response(status, data, headers = {}) {
    return {
        status, ok: status >= 200 && status < 300,
        headers: { get(name) { return headers[name.toLowerCase()] || null; } },
        async json() { if (data instanceof Error) throw data; return data; }
    };
}
async function scan(result, barcode = '6009800476637') {
    nextResponse = () => Promise.resolve(result);
    const input = element('scannerInput');
    input.value = barcode;
    input.handlers.keydown({ key: 'Enter', preventDefault() {} });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(input.value, '', 'A submitted scan must clear before the next scan');
    assert.equal(new URL(requests.at(-1), 'https://example.test').searchParams.get('barcode'), barcode);
}
(async () => {
    // Production failure: Vercel challenge JSON has no application's `message` field.
    await scan(response(403, { error: { code: 'CHALLENGE_REQUIRED' } }, { 'x-vercel-mitigated': 'challenge' }));
    assert.match(messages.at(-1).message, /verification/i);
    assert.doesNotMatch(messages.at(-1).message, /not found/i);
    assert.equal(added.length, 0);
    assert.notEqual(element('barcodeLookupNotice').style.display, 'none');
    assert.match(element('barcodeLookupMessage').textContent, /refresh/i);
    const before = requests.length;
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests.length, before, 'A blocked scan must not be replayed automatically');

    await scan(response(403, new SyntaxError('HTML challenge'), { 'x-vercel-mitigated': 'challenge' }));
    assert.match(messages.at(-1).message, /verification/i);
    await scan(response(403, { error: 'Forbidden' }));
    assert.match(messages.at(-1).message, /blocked/i);
    await scan(response(429, { error: 'Rate limit' }));
    assert.match(messages.at(-1).message, /busy|wait/i);
    await scan(response(503, { message: 'Item not found.' }));
    assert.match(messages.at(-1).message, /unavailable/i);
    await scan(response(401, {}));
    assert.match(messages.at(-1).message, /session expired/i);
    assert.equal(added.length, 0);
    console.log('PASS blocked/challenged, rate-limited, unavailable and expired-session scans never become missing products or add cart items');

    await scan(response(404, { success: false, message: 'Item not found for barcode: 999' }), '999');
    assert.equal(messages.at(-1).message, 'Item not found for barcode: 999');
    assert.equal(element('barcodeLookupNotice').style.display, 'none');
    await scan(response(409, { success: false, message: 'Expired item cannot be sold: Test' }));
    assert.match(messages.at(-1).message, /Expired item/);
    await scan(response(200, { success: false, out_of_stock: true, item: { item_name: 'Test' } }));
    assert.match(messages.at(-1).message, /Out of stock/);
    assert.equal(messages.at(-1).type, 'warning');
    assert.equal(added.length, 0);
    console.log('PASS genuine missing, expired and out-of-stock products retain their correct messages');

    for (const bad of [null, {}, [], { success: true }, { success: true, item: {} }, { success: true, item: { item_id: 'bad' } }, new SyntaxError('Unexpected HTML')]) {
        await scan(response(200, bad));
        assert.match(messages.at(-1).message, /lookup failed/i);
        assert.doesNotMatch(messages.at(-1).message, /not found/i);
    }
    nextResponse = () => Promise.reject(new TypeError('Network unavailable'));
    await context.lookupBarcode('6009800476637');
    assert.match(messages.at(-1).message, /lookup failed/i);
    assert.equal(added.length, 0);
    console.log('PASS network and malformed responses cannot be mistaken for absent products');

    const product = { item_id: 30002, barcode: '6009800476637', item_name: 'Marlboro Red', stock_qty: 12, price: 200, unit: 'packs' };
    await scan(response(200, { success: true, item: product }));
    await scan(response(200, { success: true, item: product }));
    await scan(response(200, { success: true, item: { ...product, barcode: '0012345678905' } }), '0012345678905');
    assert.equal(added.length, 3, 'Each successful scan should add exactly once');
    assert.equal(focused, 3);
    assert.equal(element('barcodeLookupNotice').style.display, 'none');
    console.log('PASS recovery, repeated scans and leading-zero barcodes without replaying failed scans');
})().catch(error => { console.error(error); process.exitCode = 1; });
