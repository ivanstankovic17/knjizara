<x-shop title="Prijava" script="resources/js/login.js">
    <div class="kolone">
        <form id="prijava" class="forma" novalidate>
            <h2>Imam nalog</h2>

            <label for="p-email">Email adresa</label>
            <input id="p-email" name="email" type="email" required autocomplete="username"
                   aria-describedby="g-p-email">
            <p class="greska" id="g-p-email" data-greska="email"></p>

            <label for="p-password">Lozinka</label>
            <input id="p-password" name="password" type="password" required
                   autocomplete="current-password" aria-describedby="g-p-password">
            <p class="greska" id="g-p-password" data-greska="password"></p>

            {{-- Pojavljuje se tek kada server traži kod druge faze. --}}
            <div data-kod hidden>
                <label for="p-code">Kod iz aplikacije za autentifikaciju</label>
                <input id="p-code" name="code" inputmode="numeric" autocomplete="one-time-code"
                       pattern="\d{6}" maxlength="6" aria-describedby="g-p-code">
                <p class="greska" id="g-p-code" data-greska="code"></p>
            </div>

            <label class="kvacica"><input type="checkbox" name="remember" value="1"> Zapamti me</label>

            <button type="submit">Prijavi se</button>
            <p><a href="{{ route('github.redirect') }}">Prijava preko GitHub-a</a></p>
            <p class="status" role="status" aria-live="polite"></p>
        </form>

        <form id="registracija" class="forma" novalidate>
            <h2>Novi kupac</h2>

            <label for="r-name">Ime i prezime</label>
            <input id="r-name" name="name" required maxlength="120" autocomplete="name"
                   aria-describedby="g-r-name">
            <p class="greska" id="g-r-name" data-greska="name"></p>

            <label for="r-email">Email adresa</label>
            <input id="r-email" name="email" type="email" required autocomplete="email"
                   aria-describedby="g-r-email">
            <p class="greska" id="g-r-email" data-greska="email"></p>

            <label for="r-password">Lozinka (bar 10 znakova, slova i cifre)</label>
            <input id="r-password" name="password" type="password" required minlength="10"
                   autocomplete="new-password" aria-describedby="g-r-password">
            <p class="greska" id="g-r-password" data-greska="password"></p>

            <label for="r-password2">Ponovite lozinku</label>
            <input id="r-password2" name="password_confirmation" type="password" required
                   autocomplete="new-password">

            <button type="submit">Napravi nalog</button>
            <p class="status" role="status" aria-live="polite"></p>
        </form>

        <form id="zaboravljena" class="forma" novalidate>
            <h2>Zaboravljena lozinka</h2>
            <label for="z-email">Email adresa</label>
            <input id="z-email" name="email" type="email" required autocomplete="email"
                   aria-describedby="g-z-email">
            <p class="greska" id="g-z-email" data-greska="email"></p>
            <button type="submit">Pošalji link</button>
            <p class="status" role="status" aria-live="polite"></p>
        </form>
    </div>
</x-shop>
