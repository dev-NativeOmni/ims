/**
 * Ekspor gambar berukuran F4 tetap (215 x 330 mm, 200 dpi) untuk tombol "Unduh PNG" laporan & grafik.
 *
 * Dulu tiap tombol memotret elemen dengan ukuran layar masing-masing: hasilnya beda-beda per perangkat
 * dan sering terpotong (mis. padding tambahan membuat teks melipat lalu baris terakhir hilang).
 * Di sini elemen/grafik selalu digambar UTUH lalu diperkecil agar muat di satu halaman F4.
 *
 * Pemakaian (tersedia global sebagai window.f4Export):
 *   f4Export.elementToF4Png(el, { fileName, orientation: 'portrait' })      // kartu/laporan HTML
 *   f4Export.canvasToF4Png(canvas, { title, subtitle, fileName })            // grafik (orientasi otomatis)
 */
const DPI = 200;
const PX_PER_MM = DPI / 25.4;
const PAGE = {
    portrait: [Math.round(215 * PX_PER_MM), Math.round(330 * PX_PER_MM)],
    landscape: [Math.round(330 * PX_PER_MM), Math.round(215 * PX_PER_MM)],
};
const MARGIN = Math.round(10 * PX_PER_MM);

function newPage(orientation) {
    const [width, height] = PAGE[orientation] ?? PAGE.portrait;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    return [canvas, ctx];
}

/** Gambar sumber diperkecil/diperbesar agar muat di kotak, rata tengah horizontal. */
function drawFitted(ctx, source, box, verticalAlign = 'top') {
    const scale = Math.min(box.width / source.width, box.height / source.height);
    const width = source.width * scale;
    const height = source.height * scale;
    const x = box.x + (box.width - width) / 2;
    const y = verticalAlign === 'center' ? box.y + (box.height - height) / 2 : box.y;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(source, x, y, width, height);
}

/** Teks satu baris; ukuran huruf mengecil sampai muat lebar yang tersedia. */
function drawFittedText(ctx, text, centerX, y, maxWidth, size, { weight = 'bold', color = '#111827' } = {}) {
    let fontSize = size;
    do {
        ctx.font = `${weight} ${fontSize}px Arial, sans-serif`;
        fontSize -= 2;
    } while (ctx.measureText(text).width > maxWidth && fontSize > 12);
    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.fillText(text, centerX, y);
}

function save(canvas, fileName) {
    const a = document.createElement('a');
    a.download = fileName.endsWith('.png') ? fileName : `${fileName}.png`;
    a.href = canvas.toDataURL('image/png');
    document.body.appendChild(a);
    a.click();
    a.remove();
}

/**
 * Kartu/laporan HTML -> PNG F4. Elemen digandakan di luar layar dengan lebar tetap (tidak tergantung
 * lebar layar), dipotret utuh, lalu dimuatkan ke halaman F4.
 */
export async function elementToF4Png(element, { fileName = 'laporan', orientation = 'portrait', captureWidth = 1024 } = {}) {
    if (!window.htmlToImage) {
        throw new Error('Pustaka html-to-image belum dimuat.');
    }

    const holder = document.createElement('div');
    holder.style.cssText = `position:fixed;left:-30000px;top:0;width:${captureWidth}px;background:#ffffff;`;
    const clone = element.cloneNode(true);
    if (clone.id) {
        clone.id = `${clone.id}-f4export`;
    }
    // Lebar tetap & tanpa margin: margin auto ikut terbawa sebagai jarak kiri saat dipotret (isi terpotong kanan).
    clone.style.margin = '0';
    clone.style.width = `${captureWidth}px`;
    clone.style.maxWidth = 'none';
    holder.appendChild(clone);
    document.body.appendChild(holder);

    try {
        if (document.fonts?.ready) {
            await document.fonts.ready;
        }
        const shot = await window.htmlToImage.toCanvas(clone, {
            pixelRatio: 2,
            backgroundColor: '#ffffff',
            width: clone.scrollWidth,
            height: clone.scrollHeight,
        });
        const [page, ctx] = newPage(orientation);
        drawFitted(ctx, shot, { x: MARGIN, y: MARGIN, width: page.width - 2 * MARGIN, height: page.height - 2 * MARGIN });
        save(page, fileName);
    } finally {
        holder.remove();
    }
}

/**
 * Grafik (canvas) -> halaman F4 berjudul. Mengembalikan canvas halamannya (bisa dipakai untuk cetak).
 */
export function canvasToF4Page(source, { title = '', subtitle = 'TAD-SMAIA7', orientation = 'auto' } = {}) {
    // 'auto': grafik tinggi (mis. batang per murid) -> portrait, grafik lebar -> landscape.
    const resolved = orientation === 'auto' ? (source.height > source.width ? 'portrait' : 'landscape') : orientation;
    const [page, ctx] = newPage(resolved);
    const usable = page.width - 2 * MARGIN;
    let top = MARGIN;

    if (title) {
        drawFittedText(ctx, title, page.width / 2, top + 52, usable, 56);
        top += 72;
    }
    if (subtitle) {
        drawFittedText(ctx, subtitle, page.width / 2, top + 34, usable, 32, { weight: 'normal', color: '#6B7280' });
        top += 52;
    }
    if (title || subtitle) {
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(MARGIN, top + 10);
        ctx.lineTo(page.width - MARGIN, top + 10);
        ctx.stroke();
        top += 40;
    }

    drawFitted(ctx, source, { x: MARGIN, y: top, width: usable, height: page.height - top - MARGIN }, 'center');

    return page;
}

export function canvasToF4Png(source, { fileName, ...options } = {}) {
    save(canvasToF4Page(source, options), fileName || options.title || 'grafik');
}
