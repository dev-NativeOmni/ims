import * as flow from '../ummi-flow';

/**
 * Timer Alur Pembelajaran Ummi (halaman ummi-flow.index): sesi berurutan dengan durasi dari pengaturan.
 * Status disimpan di localStorage per kelas & tanggal (muat ulang halaman tidak mengulang timer).
 * Pengingat: bunyi pendek 1 menit sebelum habis; saat habis bunyi + getar + notifikasi, dan diulang
 * tiap menit selama lewat waktu sampai musyrif pindah sesi.
 */
export default (sessions = [], storageKey = 'ummiFlow') => ({
    sessions,
    state: flow.initialState(),
    now: Date.now(),
    warned: {},
    alarmedAt: {},
    audio: null,
    wakeLock: null,
    finished: false,

    init() {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
            if (saved && typeof saved.index === 'number' && saved.index < this.sessions.length) this.state = saved;
            this.finished = !!(saved && saved.finished);
        } catch (e) { /* abaikan */ }
        setInterval(() => this.tick(), 250);
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && this.running) this.keepAwake(); });
    },

    get running() { return this.state.startedAt !== null && this.state.pausedAt === null && !this.finished; },
    get started() { return this.state.startedAt !== null; },
    get current() { return this.sessions[this.state.index] || null; },
    get remainingMs() { return flow.remaining(this.sessions, this.state, this.now); },
    get overtime() { return this.started && this.remainingMs < 0; },
    get clock() { return flow.formatClock(this.remainingMs); },
    get progress() {
        const total = (this.current?.minutes || 1) * 60000;
        return Math.min(100, Math.max(0, (flow.elapsed(this.state, this.now) / total) * 100));
    },
    sessionStatus(i) {
        if (i < this.state.index || (this.finished && i === this.state.index)) return 'done';
        return i === this.state.index ? 'current' : 'next';
    },

    save() {
        try { localStorage.setItem(storageKey, JSON.stringify({ ...this.state, finished: this.finished })); } catch (e) { /* abaikan */ }
    },
    start() {
        this.unlockAudio();
        this.keepAwake();
        if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
        this.finished = false;
        this.state = flow.start(this.state, Date.now());
        this.save();
    },
    pause() { this.state = flow.pause(this.state, Date.now()); this.save(); },
    next() {
        const last = this.state.index >= this.sessions.length - 1;
        this.state = flow.next(this.sessions, this.state, Date.now());
        if (last) this.finished = true;
        this.save();
    },
    reset() {
        if (this.started && !confirm('Ulang alur pembelajaran dari awal?')) return;
        this.state = flow.initialState();
        this.finished = false;
        this.warned = {};
        this.alarmedAt = {};
        this.save();
    },

    tick() {
        this.now = Date.now();
        if (!this.running) return;
        const i = this.state.index;
        const left = this.remainingMs;
        if (left <= 60000 && left > 0 && !this.warned[i]) {
            this.warned[i] = true;
            this.beep(1, 660);
        }
        if (left <= 0 && (!this.alarmedAt[i] || this.now - this.alarmedAt[i] >= 60000)) {
            const first = !this.alarmedAt[i];
            this.alarmedAt[i] = this.now;
            this.beep(first ? 3 : 2, 880);
            if (navigator.vibrate) navigator.vibrate([300, 150, 300, 150, 300]);
            if (first) this.notify();
        }
    },

    notify() {
        const nextSession = this.sessions[this.state.index + 1];
        const body = nextSession ? `Lanjut ke: ${nextSession.label} (${nextSession.minutes} menit)` : 'Semua sesi selesai. Tutup pembelajaran.';
        try {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(`Waktu ${this.current?.label || 'sesi'} habis`, { body, tag: 'ummi-flow' });
            }
        } catch (e) { /* abaikan */ }
    },
    unlockAudio() {
        try {
            this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
            if (this.audio.state === 'suspended') this.audio.resume();
        } catch (e) { this.audio = null; }
    },
    beep(times = 1, frequency = 880) {
        if (!this.audio) return;
        for (let n = 0; n < times; n++) {
            const at = this.audio.currentTime + n * 0.35;
            const osc = this.audio.createOscillator();
            const gain = this.audio.createGain();
            osc.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, at);
            gain.gain.exponentialRampToValueAtTime(0.4, at + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.25);
            osc.connect(gain).connect(this.audio.destination);
            osc.start(at);
            osc.stop(at + 0.3);
        }
    },
    async keepAwake() {
        try {
            if ('wakeLock' in navigator && !this.wakeLock) {
                this.wakeLock = await navigator.wakeLock.request('screen');
                this.wakeLock.addEventListener('release', () => { this.wakeLock = null; });
            }
        } catch (e) { /* tidak didukung */ }
    },
});
