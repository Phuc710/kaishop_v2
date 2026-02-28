(function (global) {
    function toFiniteNumber(value) {
        if (value === null || value === undefined || value === '') return null;
        var n = Number(value);
        return Number.isFinite(n) ? n : null;
    }

    function nowTs() {
        return Math.floor(Date.now() / 1000);
    }

    function cloneState(state) {
        return state ? Object.assign({}, state) : null;
    }

    function KaiMaintenanceRuntime(options) {
        var opts = options || {};
        this.statusUrl = String(opts.statusUrl || '');
        this.pollMs = Math.max(3000, Number(opts.pollMs || 10000));
        this.fetchOptions = Object.assign({ credentials: 'same-origin', cache: 'no-store' }, opts.fetchOptions || {});

        this._state = null;
        this._serverBaseTs = null;
        this._serverBaseMs = 0;
        this._tickTimer = null;
        this._pollTimer = null;
        this._listeners = [];
    }

    KaiMaintenanceRuntime.prototype.onUpdate = function (listener) {
        if (typeof listener !== 'function') return function () { };
        this._listeners.push(listener);
        return function () {
            this._listeners = this._listeners.filter(function (fn) { return fn !== listener; });
        }.bind(this);
    };

    KaiMaintenanceRuntime.prototype.start = function () {
        var self = this;
        this.stop();
        this._tickTimer = window.setInterval(function () { self._emit(); }, 1000);
        this._fetchAndSchedule(0);
    };

    KaiMaintenanceRuntime.prototype.stop = function () {
        if (this._tickTimer) window.clearInterval(this._tickTimer);
        if (this._pollTimer) window.clearTimeout(this._pollTimer);
        this._tickTimer = null;
        this._pollTimer = null;
    };

    KaiMaintenanceRuntime.prototype.getState = function () {
        return cloneState(this._state);
    };

    KaiMaintenanceRuntime.prototype.getServerNowTs = function () {
        if (!Number.isFinite(this._serverBaseTs)) return nowTs();
        var elapsed = Math.floor((Date.now() - this._serverBaseMs) / 1000);
        return this._serverBaseTs + Math.max(0, elapsed);
    };

    KaiMaintenanceRuntime.prototype.getSecondsUntilStart = function () {
        if (!this._state) return null;
        var startTs = toFiniteNumber(this._state.start_at_ts);
        if (startTs !== null) return Math.max(0, Math.floor(startTs - this.getServerNowTs()));

        var fromApi = toFiniteNumber(this._state.seconds_until_start);
        if (fromApi === null) return null;
        var elapsed = Math.floor((Date.now() - this._serverBaseMs) / 1000);
        return Math.max(0, Math.floor(fromApi - Math.max(0, elapsed)));
    };

    KaiMaintenanceRuntime.prototype.getSecondsUntilEnd = function () {
        if (!this._state) return null;
        var endTs = toFiniteNumber(this._state.end_at_ts);
        if (endTs !== null) return Math.max(0, Math.floor(endTs - this.getServerNowTs()));

        var fromApi = toFiniteNumber(this._state.seconds_until_end);
        if (fromApi === null) return null;
        var elapsed = Math.floor((Date.now() - this._serverBaseMs) / 1000);
        return Math.max(0, Math.floor(fromApi - Math.max(0, elapsed)));
    };

    KaiMaintenanceRuntime.prototype.getNoticeSecondsLeft = function () {
        if (!this._state) return null;
        var secondsUntilStart = this.getSecondsUntilStart();
        if (secondsUntilStart === null) return null;

        var noticeMinutes = toFiniteNumber(this._state.notice_minutes);
        if (noticeMinutes === null || noticeMinutes <= 0) noticeMinutes = 5;
        var noticeWindow = Math.floor(noticeMinutes * 60);
        var forcedNotice = !!this._state.notice_active;

        if (forcedNotice || secondsUntilStart <= noticeWindow) {
            return Math.max(0, secondsUntilStart);
        }

        return null;
    };

    KaiMaintenanceRuntime.prototype._fetchAndSchedule = function (delayMs) {
        var self = this;
        this._pollTimer = window.setTimeout(function () {
            if (!self.statusUrl) {
                self._fetchAndSchedule(self.pollMs);
                return;
            }

            fetch(self.statusUrl, self.fetchOptions)
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    var m = json && json.maintenance ? json.maintenance : null;
                    if (!m) return;

                    self._state = m;
                    var serverTs = toFiniteNumber(m.server_time_ts);
                    if (serverTs !== null) {
                        self._serverBaseTs = Math.floor(serverTs);
                        self._serverBaseMs = Date.now();
                    }

                    self._emit();
                })
                .catch(function () { })
                .finally(function () {
                    self._fetchAndSchedule(self.pollMs);
                });
        }, Math.max(0, Number(delayMs || 0)));
    };

    KaiMaintenanceRuntime.prototype._emit = function () {
        var snapshot = this.getState();
        var payload = {
            state: snapshot,
            serverNowTs: this.getServerNowTs(),
            secondsUntilStart: this.getSecondsUntilStart(),
            secondsUntilEnd: this.getSecondsUntilEnd(),
            noticeSecondsLeft: this.getNoticeSecondsLeft()
        };

        this._listeners.forEach(function (listener) {
            try { listener(payload); } catch (e) { }
        });
    };

    global.KaiMaintenanceRuntime = KaiMaintenanceRuntime;
})(window);

