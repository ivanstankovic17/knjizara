@props(['title' => config('app.name')])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 40rem; margin: 2rem auto; padding: 0 1rem; }
        .gotovo { color: #1a7f37; }
        .uskoro { color: #6e7781; }
    </style>
</head>
<body>
    <header>
        <h1>{{ config('app.name') }}</h1>
    </header>

    <main>
        {{ $slot }}
    </main>
</body>
</html>
