<x-shop title="Moj nalog" script="resources/js/account.js">
    @php($user = auth()->user())

    <section class="forma">
        <p><strong>{{ $user->name }}</strong> · {{ $user->email }}</p>

        @if (request('potvrdjeno'))
            <p class="uspeh">Adresa je potvrđena. Sada možete da poručujete.</p>
        @elseif (! $user->hasVerifiedEmail())
            <p class="greska">Adresa još nije potvrđena, pa poručivanje nije moguće.
                <button type="button" id="ponovo-posalji">Pošalji link ponovo</button></p>
        @endif

        <p><button type="button" id="odjava">Odjavi se</button></p>
    </section>

    <h2>Moje porudžbine</h2>
    <p id="status-porudzbina" class="status" role="status" aria-live="polite"></p>
    <ul id="porudzbine" class="porudzbine"></ul>

    <h2>Dvofaktorska prijava</h2>
    <section id="dva-faktora" class="forma" data-ukljuceno="{{ $user->hasTwoFactor() ? '1' : '0' }}">
        <p data-stanje></p>
        <div data-podesavanje hidden>
            <p>Unesite ovu tajnu u aplikaciju za autentifikaciju (Google Authenticator,
                Microsoft Authenticator, Aegis…), pa upišite kod koji aplikacija prikaže.</p>
            <p><code data-tajna></code></p>
            <label for="d-code">Kod</label>
            <input id="d-code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
        </div>
        <button type="button" data-dugme></button>
        <p class="greska" data-greska></p>
    </section>
</x-shop>
