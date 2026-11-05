Poštovani/a {{ $order->user->name }},

primili smo vašu porudžbinu broj {{ $order->id }}.

@foreach ($order->items as $item)
- {{ $item->book->title }} × {{ $item->quantity }} = {{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }} RSD
@endforeach

Ukupno: {{ number_format($order->total, 0, ',', '.') }} RSD

Obavestićemo vas kada porudžbina bude poslata.

Knjižara
