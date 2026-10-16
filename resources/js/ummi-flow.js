/**
 * Hitungan waktu timer Alur Pembelajaran Ummi (resources/js/components/ummiFlowTimer.js). Fungsi murni
 * supaya bisa dites (tests/js/ummi-flow.test.js). Status: { index, startedAt, pausedAt, pausedTotal }
 * dalam milidetik (Date.now()); startedAt null = sesi belum dimulai.
 */

export const initialState = () => ({ index: 0, startedAt: null, pausedAt: null, pausedTotal: 0 });

export function elapsed(state, now) {
    if (state.startedAt === null) return 0;
    return Math.max(0, (state.pausedAt ?? now) - state.startedAt - state.pausedTotal);
}

/** Sisa waktu sesi berjalan (negatif = lewat waktu). */
export function remaining(sessions, state, now) {
    const session = sessions[state.index];
    if (!session) return 0;
    return session.minutes * 60000 - elapsed(state, now);
}

export function start(state, now) {
    if (state.startedAt !== null && state.pausedAt === null) return state;
    if (state.pausedAt !== null) {
        return { ...state, pausedTotal: state.pausedTotal + (now - state.pausedAt), pausedAt: null };
    }
    return { ...state, startedAt: now };
}

export function pause(state, now) {
    if (state.startedAt === null || state.pausedAt !== null) return state;
    return { ...state, pausedAt: now };
}

/** Lanjut ke sesi berikutnya; sesi baru langsung berjalan bila timer sedang berjalan. */
export function next(sessions, state, now) {
    if (state.index >= sessions.length - 1) return { ...state, pausedAt: state.pausedAt ?? now, finished: true };
    const running = state.startedAt !== null && state.pausedAt === null;
    return { index: state.index + 1, startedAt: running ? now : null, pausedAt: null, pausedTotal: 0 };
}

/** "05:00", lewat waktu "+01:23". */
export function formatClock(ms) {
    const over = ms < 0;
    // Sisa waktu dibulatkan ke atas (00:01 sampai benar-benar habis); lewat waktu dibulatkan ke bawah.
    const seconds = over ? Math.floor(-ms / 1000) : Math.ceil(ms / 1000);
    const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
    const ss = String(seconds % 60).padStart(2, '0');
    return (over ? '+' : '') + mm + ':' + ss;
}

/** Jam mulai tiap sesi bila dimulai pada `startMs` tanpa jeda: [{ start, end }] (ms). */
export function schedule(sessions, startMs) {
    let cursor = startMs;
    return sessions.map((session) => {
        const slot = { start: cursor, end: cursor + session.minutes * 60000 };
        cursor = slot.end;
        return slot;
    });
}
