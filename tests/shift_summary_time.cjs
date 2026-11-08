const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');

if (process.argv[2] !== '--worker') {
    for (const TZ of ['UTC', 'Asia/Manila', 'America/Los_Angeles']) {
        process.stdout.write(execFileSync(process.execPath, [__filename, '--worker'], {
            env: { ...process.env, TZ }, encoding: 'utf8'
        }));
    }
} else {
    const source = fs.readFileSync(path.join(__dirname, '../includes/shift_modals.php'), 'utf8');
    const snippet = source.slice(source.indexOf('window.viewShiftSummary ='), source.indexOf("// Today's summary: Whole Day Storewide"));
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) elements.set(id, { textContent: '', style: {} });
        return elements.get(id);
    };
    let data, shown = 0;
    const context = vm.createContext({
        console, document: { getElementById: element },
        fetch: async () => ({ json: async () => ({ success: true, data }) }),
        showToast() { throw new Error('Summary unexpectedly failed'); }
    });
    context.window = context;
    context.shiftModals = { live: { show() { shown++; } } };
    vm.runInContext(snippet, context);
    (async () => {
        data = { has_active_shift: true, start_time: '2026-11-08 10:26:57',
            start_time_unix: Date.parse('2026-11-08T10:26:57Z') / 1000,
            cashier_name: 'Test Cashier', starting_cash: 500, txn_count: 8,
            total_sales: 1742.5, expected_cash: 2242.5 };
        await context.viewShiftSummary();
        assert.equal(element('liveShiftStarted').textContent, '06:26 PM');
        assert.equal(element('liveExpectedCash').textContent, '₱2,242.50');
        // The same instant from an already-local database must not gain another 8 hours.
        data.start_time = '2026-11-08 18:26:57';
        await context.viewShiftSummary();
        assert.equal(element('liveShiftStarted').textContent, '06:26 PM');
        data.start_time_unix = Date.parse('2026-11-08T17:05:00Z') / 1000;
        await context.viewShiftSummary();
        assert.equal(element('liveShiftStarted').textContent, '01:05 AM');
        data.start_time_unix = null;
        await context.viewShiftSummary();
        assert.equal(element('liveShiftStarted').textContent, '--');
        data = { has_active_shift: false };
        await context.viewShiftSummary();
        assert.equal(element('liveShiftActiveContent').style.display, 'none');
        assert.equal(element('liveShiftEmptyContent').style.display, '');
        assert.equal(shown, 5);
        console.log(`PASS shift-start Philippine time, midnight, missing time, totals and no-active-shift on ${process.env.TZ} browser`);
    })().catch(error => { console.error(error); process.exitCode = 1; });
}
