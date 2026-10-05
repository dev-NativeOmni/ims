{{--
    Mode Trial (lihat saja, App\Support\ReadOnlyAccess): banner + pilihan "Lihat sebagai", dan tombol
    aksi (form POST, tautan tambah/ubah/ekspor/unduh/cetak) tampil abu-abu & tidak bisa dipakai.
    Ini hanya tampilan; penjaga sebenarnya di server (BlockReadOnlyWrites).
    Form yang tetap boleh dikirim diberi atribut data-read-only-allowed (keluar, berhenti impersonasi, ganti role trial).
--}}
@php
    $viewRoleName = session(\App\Support\ReadOnlyAccess::VIEW_ROLE_KEY, \App\Support\ReadOnlyAccess::VIEWS_AS);
    $viewRoles = \App\Models\Role::query()->where('name', '!=', \App\Support\ReadOnlyAccess::ROLE)->orderBy('id')->get();
    $sampleUser = session(\App\Support\ReadOnlyAccess::SESSION_KEY) ? auth()->user() : null;
@endphp

<div class="{{ session('read_only_blocked') ? 'bg-amber-600' : 'bg-sky-700' }} text-white px-4 py-2 text-xs sm:text-sm font-medium flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))]">
    <div class="flex items-start sm:items-center gap-2 flex-1 min-w-0">
        <x-heroicon-o-eye class="w-4 h-4 shrink-0 mt-0.5 sm:mt-0" />
        <span>
            @if (session('read_only_blocked'))
                <strong>Halaman tadi tidak dapat dibuka.</strong>
            @else
                <strong>Mode Trial:</strong>
            @endif
            hanya untuk melihat; tombol tambah, ubah, hapus, unggah, unduh, dan cetak tidak aktif.
            @if ($sampleUser)
                <span class="opacity-90">Data dari akun contoh <strong>{{ $sampleUser->name }}</strong>.</span>
            @endif
        </span>
    </div>
    <form method="POST" action="{{ route('trial.view-as') }}" data-read-only-allowed class="flex items-center gap-2 shrink-0">
        @csrf
        <label for="trial-view-as" class="whitespace-nowrap">Lihat sebagai</label>
        <select id="trial-view-as" name="role" onchange="this.form.submit()"
                class="rounded-lg border-0 bg-white/15 text-white text-xs font-semibold py-1 pl-2 pr-8 focus:ring-2 focus:ring-white/60">
            @foreach ($viewRoles as $viewRole)
                <option value="{{ $viewRole->name }}" @selected($viewRole->name === $viewRoleName) class="text-zinc-900">{{ $viewRole->display_name ?: $viewRole->name }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="underline">Pilih</button></noscript>
    </form>
</div>

<div id="read-only-toast" role="status" aria-live="polite"
     class="fixed left-1/2 -translate-x-1/2 bottom-24 sm:bottom-8 z-[100] hidden max-w-[calc(100%-2rem)] px-4 py-2.5 rounded-xl bg-zinc-900 text-white text-xs sm:text-sm font-semibold shadow-lg">
    Tidak tersedia di Mode Trial (hanya lihat).
</div>

<style>
    .ro-disabled { opacity: .45 !important; cursor: not-allowed !important; filter: grayscale(.4); }
</style>
<script>
    (() => {
        // Tautan ke halaman yang diblokir server (ReadOnlyAccess::BLOCKED_ROUTE_PATTERNS).
        const blockedPath = /\/create(?:\/|$)|\/edit$|export|download|print|fast-input|whatsapp|^\/(?:spreadsheet-input|profile|users|superadmin|database-backups|audit-logs)(?:\/|$)/i;
        const isBlockedHref = (href) => {
            try {
                const url = new URL(href, window.location.href);
                return url.origin === window.location.origin && blockedPath.test(url.pathname);
            } catch (e) {
                return false;
            }
        };
        const toast = document.getElementById('read-only-toast');
        let toastTimer;
        const notify = () => {
            toast.classList.remove('hidden');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.add('hidden'), 2500);
        };
        const isBlockedForm = (form) => form instanceof HTMLFormElement
            && (form.getAttribute('method') || 'get').toLowerCase() === 'post'
            && ! form.hasAttribute('data-read-only-allowed');
        const disable = (el) => {
            if (el.classList.contains('ro-disabled')) return;
            el.classList.add('ro-disabled');
            el.setAttribute('aria-disabled', 'true');
            el.setAttribute('title', 'Tidak tersedia di Mode Trial');
        };
        const mark = (root) => {
            root.querySelectorAll('form').forEach((form) => {
                if (isBlockedForm(form)) form.querySelectorAll('button, input[type="submit"], input[type="image"]').forEach(disable);
            });
            root.querySelectorAll('button[form]').forEach((button) => {
                if (isBlockedForm(document.getElementById(button.getAttribute('form')))) disable(button);
            });
            root.querySelectorAll('a[href]').forEach((link) => { if (isBlockedHref(link.getAttribute('href'))) disable(link); });
            root.querySelectorAll('button[onclick*="print"]').forEach(disable);
        };

        document.addEventListener('click', (event) => {
            if (event.target.closest('.ro-disabled')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notify();
            }
        }, true);
        document.addEventListener('submit', (event) => {
            if (isBlockedForm(event.target)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notify();
            }
        }, true);

        // Aksi lewat fetch (input cepat dsb.): tidak dikirim, langsung dijawab 403.
        const originalFetch = window.fetch;
        window.fetch = (input, init = {}) => {
            const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            if (! ['GET', 'HEAD'].includes(method)) {
                notify();
                return Promise.resolve(new Response(JSON.stringify({ message: 'Tidak tersedia di Mode Trial.' }), { status: 403, headers: { 'Content-Type': 'application/json' } }));
            }
            return originalFetch(input, init);
        };

        const start = () => {
            mark(document);
            new MutationObserver((mutations) => mutations.forEach((m) => m.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    mark(node.parentElement || node);
                }
            }))).observe(document.body, { childList: true, subtree: true });
        };
        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
    })();
</script>
