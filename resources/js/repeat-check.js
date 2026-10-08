/**
 * Peringatan "setoran ulangan" di form Input Setoran & Spreadsheet: ayat yang sudah lulus disetor
 * sebelum tanggal setoran (atau tercatat hafalan sebelum aplikasi) tidak dihitung lagi sebagai
 * capaian baris (aturan sama dengan HafalanProgressService::passedLineDetails). Guru tetap boleh
 * menyimpan; peringatan hanya memberi tahu.
 *
 * Riwayat: [surahId, ayatAwal, ayatAkhir, 'YYYY-MM-DD' | null (hafalan sebelum aplikasi), lineId | null]
 * dari HafalanProgressService::passedHistory().
 */

const formatDate = (date) => {
    const [, month, day] = date.split('-');
    return `${day}/${month}`;
};

/**
 * @returns {{ full: boolean, repeatedAyat: number, newAyat: number, sources: Array }|null}
 */
export function findRepeat(history, item, date) {
    const surah = parseInt(item?.surah_id, 10);
    const start = parseInt(item?.ayah_start, 10);
    const end = parseInt(item?.ayah_end, 10);
    if (!surah || !start || !end || end < start || (item.status && item.status !== 'passed')) {
        return null;
    }

    const sources = (history || []).filter(([s, from, to, day]) => s === surah && from <= end && to >= start
        && (day === null || (date && day < date)));
    if (sources.length === 0) {
        return null;
    }

    const covered = new Set();
    sources.forEach(([, from, to]) => {
        for (let ayah = Math.max(from, start); ayah <= Math.min(to, end); ayah++) covered.add(ayah);
    });
    const total = end - start + 1;
    // Hafalan sebelum aplikasi dulu, lalu setoran paling awal.
    sources.sort((a, b) => (a[3] === null ? '' : a[3]).localeCompare(b[3] === null ? '' : b[3]));

    return { full: covered.size === total, repeatedAyat: covered.size, newAyat: total - covered.size, sources };
}

export function repeatMessage(result) {
    if (!result) {
        return '';
    }
    const shown = result.sources.slice(0, 2).map(([, from, to, day]) => (day === null
        ? `hafalan sebelum aplikasi (${from}-${to})`
        : `setoran ${formatDate(day)} (${from}-${to})`));
    const more = result.sources.length > 2 ? ` +${result.sources.length - 2} lagi` : '';
    const where = shown.join(', ') + more;

    return result.full
        ? `Ayat ini sudah lulus: ${where}. Tidak menambah capaian baris. Bila ini muraja'ah/mengulang, catat di menu Muraja'ah.`
        : `${result.repeatedAyat} ayat sudah lulus: ${where}. Hanya ${result.newAyat} ayat baru yang dihitung.`;
}
