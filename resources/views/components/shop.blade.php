@props(['title', 'script'])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', $script])
</head>
<body data-prijavljen="{{ auth()->check() ? '1' : '0' }}" data-korisnik="{{ auth()->id() }}">
    <header class="zaglavlje">
        <a href="{{ route('catalog') }}" class="logo">Knjižara</a>
        <nav>
            <a href="{{ route('catalog') }}">Katalog</a>
            {{-- Meni prati dozvole; pravu proveru i dalje radi server. --}}
            @can('create', App\Models\Book::class)
                <a href="{{ route('books.create') }}">Nova knjiga</a>
            @endcan
            @can('orders.manage')
                <a href="{{ route('admin.orders') }}">Porudžbine</a>
            @endcan
        </nav>
        {{ $actions ?? '' }}
        {{-- Stranice su web rute, pa server zna ko je prijavljen (sesija). --}}
        @auth
            <a href="{{ route('account') }}" class="korisnik">{{ auth()->user()->name }}</a>
        @else
            <a href="{{ route('login') }}">Prijava</a>
        @endauth
    </header>

    <main>
        <h1>{{ $title }}</h1>
        {{ $slot }}
    </main>
</body>
</html>
