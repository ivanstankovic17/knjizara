<x-shop title="Nova lozinka" script="resources/js/login.js">
    <form id="nova-lozinka" class="forma" novalidate>
        {{-- Token i adresa stižu u linku iz mejla. --}}
        <input type="hidden" name="token" value="{{ request('token') }}">

        <label for="n-email">Email adresa</label>
        <input id="n-email" name="email" type="email" required value="{{ request('email') }}"
               autocomplete="username" aria-describedby="g-n-email">
        <p class="greska" id="g-n-email" data-greska="email"></p>

        <label for="n-password">Nova lozinka</label>
        <input id="n-password" name="password" type="password" required minlength="10"
               autocomplete="new-password" aria-describedby="g-n-password">
        <p class="greska" id="g-n-password" data-greska="password"></p>

        <label for="n-password2">Ponovite lozinku</label>
        <input id="n-password2" name="password_confirmation" type="password" required
               autocomplete="new-password">

        <button type="submit">Sačuvaj lozinku</button>
        <p class="status" role="status" aria-live="polite"></p>
    </form>
</x-shop>
