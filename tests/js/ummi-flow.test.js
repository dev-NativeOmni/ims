// Hitungan waktu timer Alur Pembelajaran Ummi (resources/js/ummi-flow.js). Jalankan: npm run test:js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initialState, start, pause, next, remaining, formatClock, schedule } from '../../resources/js/ummi-flow.js';

const sessions = [
    { key: 'pembukaan', label: 'Pembukaan', minutes: 5 },
    { key: 'murojaah', label: "Muraja'ah", minutes: 10 },
    { key: 'penutup', label: 'Penutup', minutes: 5 },
];
const MIN = 60000;

test('countdown, pause & resume', () => {
    let state = start(initialState(), 0);
    assert.equal(remaining(sessions, state, 2 * MIN), 3 * MIN);
    state = pause(state, 2 * MIN);
    assert.equal(remaining(sessions, state, 10 * MIN), 3 * MIN, 'Jeda: waktu tidak berkurang.');
    state = start(state, 10 * MIN);
    assert.equal(remaining(sessions, state, 11 * MIN), 2 * MIN);
});

test('overtime is negative and shown with a plus sign', () => {
    const state = start(initialState(), 0);
    assert.equal(formatClock(remaining(sessions, state, 6 * MIN + 23000)), '+01:23');
    assert.equal(formatClock(5 * MIN), '05:00');
    assert.equal(formatClock(1500), '00:02');
});

test('next starts the following session immediately while running', () => {
    let state = start(initialState(), 0);
    state = next(sessions, state, 7 * MIN);
    assert.equal(state.index, 1);
    assert.equal(remaining(sessions, state, 7 * MIN), 10 * MIN);
    state = next(sessions, next(sessions, state, 8 * MIN), 9 * MIN);
    assert.equal(state.finished, true, 'Sesi terakhir selesai.');
});

test('schedule gives planned start and end of each session', () => {
    assert.deepEqual(schedule(sessions, 0).map((s) => [s.start / MIN, s.end / MIN]), [[0, 5], [5, 15], [15, 20]]);
});
