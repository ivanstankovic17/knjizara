@props(['title', 'script'])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', $script])
</head>
<body>
    <header class="zaglavlje">
        <a href="{{ route('catalog') }}" class="logo">Knjižara</a>
        <nav>
            <a href="{{ route('catalog') }}">Katalog</a>
            @if (app()->isLocal())
                <a href="{{ route('books.create') }}">Nova knjiga</a>
            @endif
        </nav>
        {{ $actions ?? '' }}
    </header>

    <main>
        <h1>{{ $title }}</h1>
        {{ $slot }}
    </main>
</body>
</html>
