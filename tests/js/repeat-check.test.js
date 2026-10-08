// Logika peringatan setoran ulangan (resources/js/repeat-check.js). Jalankan: npm run test:js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { findRepeat, repeatMessage } from '../../resources/js/repeat-check.js';

// [surahId, ayat awal, ayat akhir, tanggal | null (hafalan sebelum aplikasi), lineId]
const history = [
    [8, 32, 41, '2026-08-12', 10],
    [8, 42, 49, '2026-08-18', 11],
    [96, 1, 10, null, null],
];
const setoran = (surah_id, ayah_start, ayah_end, status = 'passed') => ({ surah_id: String(surah_id), ayah_start, ayah_end, status });

test('setoran yang semua ayatnya sudah lulus sebelumnya = ulangan penuh', () => {
    const result = findRepeat(history, setoran(8, 33, 42), '2026-09-01');
    assert.equal(result.full, true);
    assert.equal(result.newAyat, 0);
    assert.match(repeatMessage(result), /^Ayat ini sudah lulus: setoran 12\/08 \(32-41\), setoran 18\/08 \(42-49\)\. Tidak menambah capaian baris/);
});

test('sebagian ayat baru: sebut jumlah ayat yang masih dihitung', () => {
    const result = findRepeat(history, setoran(8, 48, 55), '2026-09-01');
    assert.equal(result.full, false);
    assert.equal(result.newAyat, 6);
    assert.equal(repeatMessage(result), '2 ayat sudah lulus: setoran 18/08 (42-49). Hanya 6 ayat baru yang dihitung.');
});

test('hafalan sebelum aplikasi selalu dihitung sudah hafal', () => {
    assert.match(repeatMessage(findRepeat(history, setoran(96, 1, 5), '2026-07-01')), /hafalan sebelum aplikasi \(1-10\)/);
});

test('setoran di tanggal yang sama atau sesudahnya, status selain Lulus, dan isian belum lengkap tidak diperingatkan', () => {
    assert.equal(findRepeat(history, setoran(8, 32, 41), '2026-08-12'), null);
    assert.equal(findRepeat(history, setoran(8, 32, 41), '2026-08-01'), null);
    assert.equal(findRepeat(history, setoran(8, 33, 42, 'repeat'), '2026-09-01'), null);
    assert.equal(findRepeat(history, setoran(8, '', 42), '2026-09-01'), null);
    assert.equal(findRepeat(history, setoran(9, 1, 5), '2026-09-01'), null);
    assert.equal(repeatMessage(null), '');
});
