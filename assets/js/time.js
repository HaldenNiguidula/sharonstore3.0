// Shared Philippine clock, synchronized with the server rather than the device clock.
(function () {
    'use strict';
    var zone = 'Asia/Manila';
    var tick = function () { return performance.now(); };
    var anchorTick = tick();
    var boot = Number(window.SHARON_TIME_BOOT);
    var anchorTime = Number.isFinite(boot) && boot > 0 ? boot : Date.now();
    var syncing = false;

    function now() { return new Date(anchorTime + tick() - anchorTick); }
    function parse(value) {
        if (value instanceof Date) return value;
        if (typeof value === 'number') return new Date(value);
        var text = String(value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(text)) text += 'T00:00:00+08:00';
        else if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test(text)) text = text.replace(' ', 'T') + '+08:00';
        return new Date(text);
    }
    function format(value, options) {
        var date = parse(value);
        return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString('en-PH', Object.assign({}, options, {timeZone: zone}));
    }
    function dateKey(value) {
        var parts = new Intl.DateTimeFormat('en-US', {timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(value || now());
        var fields = {};
        parts.forEach(function (part) { fields[part.type] = part.value; });
        return fields.year + '-' + fields.month + '-' + fields.day;
    }
    function daysUntil(value) {
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
        if (!match || Number(match[1]) < 1 || Number(match[2]) < 1 || Number(match[2]) > 12 || Number(match[3]) < 1 || Number(match[3]) > 31) return null;
        var today = dateKey().split('-').map(Number);
        return Math.round((Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])) - Date.UTC(today[0], today[1] - 1, today[2])) / 86400000);
    }
    async function sync() {
        if (syncing) return;
        syncing = true;
        var before = tick();
        try {
            var response = await fetch('/sharonstore3.0/api/time.php', {cache: 'no-store', headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            var data = await response.json();
            var after = tick(), timestamp = Number(data.unix_ms);
            if (data.success && Number.isFinite(timestamp) && timestamp > 0) {
                anchorTime = timestamp + (after - before) / 2;
                anchorTick = after;
            }
        } catch (error) {
            // Keep advancing the last synchronized clock during a temporary connection failure.
        } finally { syncing = false; }
    }
    function refreshPrintTimes() {
        var current = now();
        document.querySelectorAll('[data-app-print-time]').forEach(function (element) {
            element.textContent = format(current, {year: 'numeric', month: 'long', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true}) + ' PHT';
        });
    }
    window.SharonTime = {now: now, parse: parse, format: format, dateKey: dateKey, daysUntil: daysUntil, sync: sync, refreshPrintTimes: refreshPrintTimes};
    sync();
    setInterval(sync, 60000);
    window.addEventListener('focus', sync);
    window.addEventListener('beforeprint', refreshPrintTimes);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) sync(); });
})();
