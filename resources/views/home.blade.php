<x-layout title="Knjižara: početna">
    <p>Aplikacija koju gradimo kroz knjigu. Stanje API-ja:
        <a href="{{ route('api.status') }}">{{ route('api.status') }}</a>
    </p>

    <h2>Mogućnosti</h2>

    <ul>
        @foreach ($features as $feature)
            <li class="{{ $feature['done'] ? 'gotovo' : 'uskoro' }}">
                {{ $feature['name'] }} (glava {{ $feature['chapter'] }})
                @if (! $feature['done'])
                    — uskoro
                @endif
            </li>
        @endforeach
    </ul>
</x-layout>
