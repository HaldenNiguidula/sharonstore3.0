const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
if (process.argv[2] !== '--worker') {
    for (const TZ of ['UTC', 'Asia/Manila', 'America/Los_Angeles']) {
        process.stdout.write(execFileSync(process.execPath, [__filename, '--worker'], { env: { ...process.env, TZ }, encoding: 'utf8' }));
    }
} else {
    const root = path.resolve(__dirname, '..');
    const realDate = Date;
    class WrongDeviceDate extends realDate {
        constructor(...args) { super(...(args.length ? args : ['2000-01-01T00:00:00Z'])); }
        static now() { return realDate.parse('2000-01-01T00:00:00Z'); }
    }
    let elapsed = 0, serverTime = realDate.parse('2026-11-08T15:59:59Z'), offline = false;
    const events = {}, elements = new Map(), printNode = { textContent: 'Old page-render time' };
    const element = id => {
        if (!elements.has(id)) elements.set(id, { textContent: '', value: '', innerHTML: '', style: {}, dataset: {}, handlers: {},
            addEventListener(event, fn) { this.handlers[event] = fn; } });
        return elements.get(id);
    };
    const context = vm.createContext({
        Date: WrongDeviceDate, Intl, console, SHARON_TIME_BOOT: serverTime, performance: { now: () => elapsed },
        document: { hidden: false, addEventListener() {}, getElementById: element, querySelectorAll: () => [printNode],
            createElement() { return { textContent: '', get innerHTML() { return this.textContent; } }; } },
        addEventListener(name, fn) { events[name] = fn; }, setInterval() {}, setTimeout() {},
        fetch: async () => {
            if (offline) throw new Error('Simulated offline clock');
            return { ok: true, json: async () => ({ success: true, unix_ms: serverTime }) };
        }
    });
    context.window = context;
    vm.runInContext(fs.readFileSync(path.join(root, 'assets/js/time.js'), 'utf8'), context);
    (async () => {
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(context.SharonTime.dateKey(), '2026-11-08');
        elapsed = 2000;
        assert.equal(context.SharonTime.dateKey(), '2026-11-09');
        assert.equal(context.SharonTime.format(context.SharonTime.now(), { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }), '12:00:01 AM');
        assert.equal(context.SharonTime.daysUntil('2026-11-09'), 0);
        assert.equal(context.SharonTime.daysUntil('2026-11-10'), 1);
        assert.equal(context.SharonTime.daysUntil('2026-11-08'), -1);
        assert.equal(context.SharonTime.daysUntil('0000-00-00'), null);
        offline = true;
        await context.SharonTime.sync(); elapsed += 60000;
        assert.equal(context.SharonTime.now().getTime(), serverTime + elapsed, 'Clock stopped while offline');
        offline = false; serverTime = realDate.parse('2026-12-31T16:00:00Z');
        await context.SharonTime.sync();
        assert.equal(context.SharonTime.dateKey(), '2027-01-01');
        events.beforeprint();
        assert.match(printNode.textContent, /2027/); assert.match(printNode.textContent, /12:00 AM PHT/);

        const app = fs.readFileSync(path.join(root, 'assets/js/app.js'), 'utf8');
        const clockStart = app.indexOf('(function startLiveClock()');
        vm.runInContext(app.slice(clockStart, app.indexOf('})();', clockStart) + 5), context);
        assert.equal(element('liveTime').textContent, '12:00:00 AM');
        assert.match(element('liveDate').textContent, /2027/);

        vm.runInContext(fs.readFileSync(path.join(root, 'assets/js/pos.js'), 'utf8'), context);
        context.tickClock(); context.updateTxnCode();
        assert.equal(element('posTime').textContent, '12:00:00 AM');
        assert.equal(element('txnCodePreview').textContent, 'TXN-20270101-XXXX');
        context.receiptM = { show() {} };
        context.showReceipt({ transaction_code: 'SAVED-TIME', cashier_name: 'Test', items: [], subtotal: 0, grand_total: 0,
            amount_tendered: 0, change_amount: 0, transaction_time_unix: realDate.parse('2026-11-08T16:05:06Z') / 1000 });
        assert.match(element('receiptContent').innerHTML, /Nov 9, 2026/);
        assert.match(element('receiptContent').innerHTML, /12:05:06 AM/);
        assert.doesNotMatch(element('receiptContent').innerHTML, /2027|2000/);

        const shifts = fs.readFileSync(path.join(root, 'shifts.php'), 'utf8');
        const rangeStart = shifts.indexOf('window.setShiftDateRange =');
        context.applyFilters = () => {};
        vm.runInContext(shifts.slice(rangeStart, shifts.indexOf('\n    };', rangeStart) + 7), context);
        context.setShiftDateRange('today');
        assert.equal(element('shiftDateFrom').value, '2027-01-01');
        context.setShiftDateRange('yesterday'); assert.equal(element('shiftDateFrom').value, '2026-12-31');
        context.setShiftDateRange('7days'); assert.equal(element('shiftDateFrom').value, '2026-12-26');
        context.setShiftDateRange('month'); assert.equal(element('shiftDateFrom').value, '2027-01-01');
        console.log(`PASS server-synced clock, wrong device date, offline ticking, PHT midnight/year/expiry, saved receipt time, print time and shift filters (${process.env.TZ})`);
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
