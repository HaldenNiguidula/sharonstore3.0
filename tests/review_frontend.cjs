const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const elements = new Map();
const timers = new Map();
const requests = [];
let timerId = 0;
function element(id) {
    if (!elements.has(id)) elements.set(id, {
        value: '', style: {}, dataset: {}, innerHTML: '', textContent: '', disabled: false,
        handlers: {}, addEventListener(event, fn) { this.handlers[event] = fn; },
        classList: { add() {}, remove() {} }, focus() {}, querySelectorAll() { return []; }
    });
    return elements.get(id);
}
const context = vm.createContext({
    console, document: {
        getElementById: element, addEventListener() {},
        createElement() { return { textContent: '', get innerHTML() { return this.textContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); } }; }
    },
    localStorage: { setItem() {} },
    setTimeout(fn) { timers.set(++timerId, fn); return timerId; },
    clearTimeout(id) { timers.delete(id); },
    fetch(url) { return new Promise(resolve => requests.push({ url, resolve })); },
    confirm() { throw new Error('Unexpected confirmation'); },
});
context.window = context;
vm.runInContext(fs.readFileSync(path.join(root, 'assets/js/pos.js'), 'utf8'), context);
const toasts = [];
context.showToast = message => toasts.push(message);
const initial = { item_id: 1, item_name: 'Initial catalog item', stock_qty: 3, price: 2, unit: 'pieces' };
const searched = { item_id: 155, item_name: 'Zonrox Regular Bleach', stock_qty: 110, price: 52, unit: 'pieces' };
context.allItems = [initial];
context.renderGrid([searched]);
context.addById(155);
assert.equal(context.cart.length, 1);
assert.equal(context.cart[0].item_id, 155, 'Search-only result could not be added');
context.cart = [];
context.renderGrid([{ ...searched, is_expired: true }]);
assert.match(element('itemGrid').innerHTML, /Expired/);
context.addById(155);
assert.equal(context.cart.length, 0, 'Expired search result was added');
assert.match(toasts.at(-1), /expired/);
console.log('PASS POS results outside initial catalog and expired product selection');

function runTimers() { const pending = [...timers.values()]; timers.clear(); pending.forEach(fn => fn()); }
const settle = () => new Promise(resolve => setImmediate(resolve));
async function searchRace() {
    context.bindEvents();
    const input = element('itemSearchInput');
    input.value = 'zonrox'; input.handlers.input.call(input); runTimers();
    input.value = 'marty'; input.handlers.input.call(input); runTimers();
    const newest = { ...initial, item_id: 42, item_name: "Marty's" };
    requests[1].resolve({ ok: true, json: async () => ({ success: true, items: [newest] }) });
    await settle();
    requests[0].resolve({ ok: true, json: async () => ({ success: true, items: [searched] }) });
    await settle();
    assert.equal(context.visibleItems[0].item_id, 42, 'Old search response replaced the current search');
    input.value = 'late'; input.handlers.input.call(input); runTimers();
    context.clearSearch();
    requests[2].resolve({ ok: true, json: async () => ({ success: true, items: [searched] }) });
    await settle();
    assert.equal(context.visibleItems[0].item_id, 1, 'Late response replaced cleared search');
    input.value = 'cancelled timer'; input.handlers.input.call(input);
    context.clearSearch(); runTimers();
    assert.equal(context.visibleItems[0].item_id, 1, 'Cancelled search timer replaced cleared search');
    input.value = 'zonrox';
    context.loadAllItems();
    requests[3].resolve({ ok: true, json: async () => ({ success: true, items: [initial] }) });
    await settle();
    requests[4].resolve({ ok: true, json: async () => ({ success: true, items: [searched] }) });
    await settle();
    assert.equal(context.visibleItems[0].item_id, 155, 'Refreshing the catalog left an active search stuck loading');
    console.log('PASS out-of-order search responses and clearing pending search');
}

function safeHandlers() {
    const shared = fs.readFileSync(path.join(root, 'assets/js/app.js'), 'utf8');
    const utilities = shared.slice(shared.indexOf('function escHtml('), shared.indexOf('/* ── Number Formatting'));
    vm.runInContext(utilities, context);
    const source = fs.readFileSync(path.join(root, 'inventory.php'), 'utf8');
    // Page-level declarations share the global scope with app.js in the browser.
    const override = source.match(/function escHtml\([^)]*\)\s*\{[\s\S]*?\n\}/);
    if (override) vm.runInContext(override[0], context);
    const handler = source.match(/onclick="(openAdjust\(\$\{item.item_id\}[^\n]+?\))"/)[1];
    const decode = value => value.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
    for (const name of ["Oishi Marty's", 'Farmer "Fresh" <Goods> & Rice', "'); throw new Error('injected'); //", 'Line\nBreak \\ Name']) {
        context.item = { item_id: 8, item_name: name };
        let captured;
        context.openAdjust = (id, value) => { assert.equal(id, 8); captured = value; };
        const attribute = vm.runInContext('`' + handler + '`', context);
        assert.equal(attribute.includes('"'), false, 'Unescaped quote can break the HTML attribute');
        vm.runInContext(decode(attribute), context);
        assert.equal(captured, name, 'Handler changed the product name');
    }
    console.log('PASS actual inventory handler with apostrophes, quotes, HTML, slashes and newlines');
}

function compileScripts() {
    for (const name of ['accounts.php', 'inventory.php', 'suppliers.php', 'reports.php', 'backup.php']) {
        const source = fs.readFileSync(path.join(root, name), 'utf8');
        for (const match of source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)) {
            new vm.Script(match[1].replace(/<\?php[\s\S]*?\?>|<\?=[\s\S]*?\?>/g, '0'), { filename: name });
        }
    }
    console.log('PASS changed-page JavaScript syntax');
}
async function backupDownload() {
    const source = fs.readFileSync(path.join(root, 'backup.php'), 'utf8');
    const start = source.indexOf("document.getElementById('btnBackup').addEventListener('click'");
    const end = source.indexOf('// ── Delete Backup', start);
    vm.runInContext(source.slice(start, end), context);
    const messages = [];
    context.showToast = (message, type) => messages.push({ message, type });
    context.fetch = async () => ({ ok: false, json: async () => ({ message: 'Simulated export failure' }) });
    const button = element('btnBackup');
    await button.handlers.click.call(button);
    assert.equal(button.disabled, false, 'Failed download left the button disabled');
    assert.equal(messages.some(message => message.type === 'success'), false, 'Failed export showed success');
    assert.equal(messages.at(-1).message, 'Simulated export failure');
    let clicked = 0, refreshed = 0, filename;
    context.URL = { createObjectURL() { return 'blob:test'; }, revokeObjectURL() {} };
    const create = context.document.createElement;
    context.document.body = { appendChild() {} };
    context.document.createElement = name => name === 'a' ? {
        set download(value) { filename = value; }, click() { clicked++; }, remove() {}
    } : create(name);
    context.loadBackups = () => refreshed++;
    context.fetch = async () => ({ ok: true, blob: async () => ({}), headers: { get: () => 'attachment; filename="complete.sql"' } });
    await button.handlers.click.call(button);
    assert.equal(clicked, 1);
    assert.equal(filename, 'complete.sql');
    assert.equal(refreshed, 1);
    assert.equal(button.disabled, false);
    assert.equal(messages.at(-1).type, 'success');
    console.log('PASS failed manual export shows an error; successful export starts the named download');
}
searchRace().then(async () => { safeHandlers(); compileScripts(); await backupDownload(); }).catch(error => { console.error(error); process.exitCode = 1; });
