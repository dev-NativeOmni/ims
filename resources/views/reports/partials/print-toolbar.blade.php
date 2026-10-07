{{--
    Toolbar halaman cetak rapor (tidak ikut tercetak). Gaya ditulis sendiri (.pt-*), bukan kelas Tailwind,
    karena halaman cetak memuat app.css + Tailwind CDN yang aturan mode gelapnya berbeda (kelas .dark vs
    prefers-color-scheme) sehingga warnanya bertabrakan. Tema mengikuti pilihan tema aplikasi (localStorage
    'theme', sama dengan layouts/app), cadangan: tema sistem.
    $title, $subtitle, $printLabel; opsional $lockedAt.
--}}
<script>
    (() => {
        let theme = null;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        const dark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.setAttribute('data-print-theme', dark ? 'dark' : 'light');
    })();
</script>
<style>
    :root, :root[data-print-theme="light"] {
        --pt-page: #e4e4e7; --pt-bg: #ffffff; --pt-border: #e4e4e7; --pt-text: #18181b; --pt-muted: #52525b;
        --pt-tip: #b45309; --pt-locked: #be123c; --pt-btn-bg: #ffffff; --pt-btn-text: #3f3f46; --pt-btn-border: #d4d4d8;
        --pt-primary: #0d9488; --pt-primary-hover: #0f766e; --pt-dot: #10b981;
    }
    :root[data-print-theme="dark"] {
        --pt-page: #09090b; --pt-bg: #18181b; --pt-border: #3f3f46; --pt-text: #fafafa; --pt-muted: #a1a1aa;
        --pt-tip: #fbbf24; --pt-locked: #fb7185; --pt-btn-bg: #27272a; --pt-btn-text: #e4e4e7; --pt-btn-border: #52525b;
        --pt-primary: #14b8a6; --pt-primary-hover: #0d9488; --pt-dot: #34d399;
    }
    @media screen { html, body { background: var(--pt-page) !important; } }
    .pt-bar {
        max-width: 215mm; margin: 0 auto 1.5rem; padding: 1rem 1.25rem; box-sizing: border-box;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem;
        background: var(--pt-bg); color: var(--pt-text); border: 1px solid var(--pt-border); border-radius: 1rem;
        box-shadow: 0 10px 25px -10px rgba(0, 0, 0, .25);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; font-size: 12px; line-height: 1.45;
    }
    .pt-info { display: flex; align-items: flex-start; gap: .75rem; min-width: 0; flex: 1 1 22rem; }
    .pt-dot { width: .6rem; height: .6rem; margin-top: .3rem; border-radius: 9999px; background: var(--pt-dot); flex-shrink: 0; }
    .pt-title { margin: 0; display: flex; align-items: center; gap: .4rem; font-size: 14px; font-weight: 700; color: var(--pt-text); }
    .pt-title svg { width: 1rem; height: 1rem; color: var(--pt-primary); flex-shrink: 0; }
    .pt-sub { margin: .15rem 0 0; color: var(--pt-muted); }
    .pt-locked, .pt-tip { margin: .35rem 0 0; display: flex; align-items: flex-start; gap: .3rem; font-size: 11px; }
    .pt-locked { color: var(--pt-locked); font-weight: 600; }
    .pt-tip { color: var(--pt-tip); }
    .pt-locked svg, .pt-tip svg { width: .9rem; height: .9rem; flex-shrink: 0; margin-top: .05rem; }
    .pt-actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
    .pt-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: .75rem; cursor: pointer;
        font: inherit; font-weight: 700; border: 1px solid var(--pt-btn-border); background: var(--pt-btn-bg); color: var(--pt-btn-text);
        transition: background-color .15s, filter .15s;
    }
    .pt-btn:hover { filter: brightness(.96); }
    .pt-btn svg { width: 1rem; height: 1rem; }
    .pt-btn-primary { background: var(--pt-primary); border-color: var(--pt-primary); color: #ffffff; }
    .pt-btn-primary:hover { background: var(--pt-primary-hover); filter: none; }
    @media print { .pt-bar { display: none !important; } }
</style>

<div class="pt-bar no-print">
    <div class="pt-info">
        <span class="pt-dot" aria-hidden="true"></span>
        <div style="min-width: 0">
            <h1 class="pt-title"><x-heroicon-o-document-text /><span>{{ $title }}</span></h1>
            <p class="pt-sub">{{ $subtitle }}</p>
            @if (! empty($lockedAt))
                <p class="pt-locked"><x-heroicon-o-lock-closed /><span>Terkunci {{ $lockedAt->locale('id')->translatedFormat('d F Y H:i') }} &mdash; data rapor dibekukan</span></p>
            @endif
            {{-- Alamat web, tanggal & nomor halaman di tepi kertas ditambahkan browser (bukan bagian rapor); @page margin 0
                 menghilangkannya di Chrome, Safari perlu opsi cetaknya dimatikan. --}}
            <p class="pt-tip"><x-heroicon-o-light-bulb /><span>Bila alamat web &amp; "Halaman 1 dari 1" ikut tercetak: di jendela cetak matikan opsi <strong>Header dan footer</strong> (Safari: "Cetak header dan footer"), atau cetak lewat Google Chrome.</span></p>
        </div>
    </div>
    <div class="pt-actions">
        <button type="button" class="pt-btn" onclick="window.close()">Tutup</button>
        <button type="button" class="pt-btn pt-btn-primary" onclick="window.print()"><x-heroicon-o-printer /><span>{{ $printLabel }}</span></button>
    </div>
</div>
