(function () {
    var defaultConfig = {
        appTimezone: 'UTC',
        displayTimezone: 'Asia/Ho_Chi_Minh',
        locale: 'vi-VN'
    };

    var cfg = Object.assign({}, defaultConfig, window.KS_TIME_CONFIG || {});

    function toTimestamp(value) {
        if (value == null || value === '') return null;
        if (typeof value === 'number' && isFinite(value)) {
            if (value > 9999999999) return Math.floor(value / 1000);
            return Math.floor(value);
        }
        var raw = String(value).trim();
        if (!raw) return null;
        if (/^\d+$/.test(raw)) {
            var num = Number(raw);
            if (!isFinite(num) || num <= 0) return null;
            if (num > 9999999999) num = Math.floor(num / 1000);
            return Math.floor(num);
        }
        var dt = new Date(raw);
        if (!isNaN(dt.getTime())) return Math.floor(dt.getTime() / 1000);
        return null;
    }

    function formatDateTime(value, options) {
        var ts = toTimestamp(value);
        if (ts == null) return '';
        options = options || {};
        try {
            return new Intl.DateTimeFormat(options.locale || cfg.locale, {
                timeZone: options.timeZone || cfg.displayTimezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).format(new Date(ts * 1000));
        } catch (e) {
            return new Date(ts * 1000).toISOString();
        }
    }

    function formatYmdHms(value, options) {
        var ts = toTimestamp(value);
        if (ts == null) return '';
        try {
            var dtf = new Intl.DateTimeFormat((options && options.locale) || cfg.locale, {
                timeZone: (options && options.timeZone) || cfg.displayTimezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            var parts = dtf.formatToParts(new Date(ts * 1000));
            var map = {};
            parts.forEach(function (p) { map[p.type] = p.value; });
            return [map.year, map.month, map.day].join('-') + ' ' + [map.hour, map.minute, map.second].join(':');
        } catch (e) {
            return new Date(ts * 1000).toISOString().slice(0, 19).replace('T', ' ');
        }
    }

    function timeAgo(value, options) {
        var ts = toTimestamp(value);
        if (ts == null) return '';
        var nowTs = Math.floor(Date.now() / 1000);
        var diff = Math.max(0, nowTs - ts);
        var units = [
            ['năm', 31536000],
            ['tháng', 2592000],
            ['tuần', 604800],
            ['ngày', 86400],
            ['giờ', 3600],
            ['phút', 60],
            ['giây', 1]
        ];
        for (var i = 0; i < units.length; i++) {
            var unit = units[i];
            var n = Math.floor(diff / unit[1]);
            if (n > 0) {
                return n + ' ' + unit[0] + ' trước';
            }
        }
        return 'vừa xong';
    }

    function formatDurationVi(totalSeconds) {
        var s = Math.max(0, Math.floor(Number(totalSeconds || 0)));
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        var parts = [];
        if (h > 0) parts.push(h + ' giờ');
        if (m > 0) parts.push(m + ' phút');
        if (sec > 0 || parts.length === 0) parts.push(sec + ' giây');
        return parts.join(' ');
    }

    window.KaiTime = window.KaiTime || {};
    window.KaiTime.config = cfg;
    window.KaiTime.toTimestamp = toTimestamp;
    window.KaiTime.formatDateTime = formatDateTime;
    window.KaiTime.formatYmdHms = formatYmdHms;
    window.KaiTime.timeAgo = timeAgo;
    window.KaiTime.formatDurationVi = formatDurationVi;
})();

