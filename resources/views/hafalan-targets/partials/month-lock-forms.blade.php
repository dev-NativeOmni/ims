{{--
    Form tersembunyi kunci/buka kunci target per bulan untuk satu kelas (di luar form simpan target;
    tombolnya ada di partials/month-lock).

    Variabel: $classId, $months (diindeks "Y-m"), $locks (diindeks "Y-m"), $canLock, $canUnlock
--}}
@if ($classId)
    @foreach (array_keys($months) as $monthKey)
        @if ($locks->has($monthKey) && $canUnlock)
            <form id="unlock-{{ $classId }}-{{ $monthKey }}" method="POST" action="{{ route('hafalan-targets.unlock') }}" class="hidden">
                @csrf
                @method('DELETE')
                <input type="hidden" name="class_room_id" value="{{ $classId }}">
                <input type="hidden" name="month" value="{{ $monthKey }}">
            </form>
        @elseif (! $locks->has($monthKey) && $canLock)
            <form id="lock-{{ $classId }}-{{ $monthKey }}" method="POST" action="{{ route('hafalan-targets.lock') }}" class="hidden">
                @csrf
                <input type="hidden" name="class_room_id" value="{{ $classId }}">
                <input type="hidden" name="month" value="{{ $monthKey }}">
            </form>
        @endif
    @endforeach
@endif
