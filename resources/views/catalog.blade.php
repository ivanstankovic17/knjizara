<x-shop title="Katalog" script="resources/js/catalog.js">
    <x-slot:actions>
        <button type="button" id="otvori-korpu">Korpa (<span id="broj-u-korpi">0</span>)</button>
    </x-slot:actions>

    <form id="filteri" class="filteri" role="search">
        <label>Pretraga
            <input type="search" name="q" placeholder="naslov ili autor" minlength="2" maxlength="100">
        </label>
        <label>Kategorija
            <select name="category"><option value="">sve</option></select>
        </label>
        <label>Cena od
            <input type="number" name="min_price" min="0" step="100" inputmode="numeric">
        </label>
        <label>do
            <input type="number" name="max_price" min="0" step="100" inputmode="numeric">
        </label>
        <label>Redosled
            <select name="sort">
                <option value="title">po naslovu</option>
                <option value="price">najjeftinije prvo</option>
                <option value="-price">najskuplje prvo</option>
                <option value="-published_year">najnovije prvo</option>
            </select>
        </label>
        <label class="kvacica">
            <input type="checkbox" name="in_stock" value="1"> samo na stanju
        </label>
    </form>

    <p id="status" class="status" role="status" aria-live="polite"></p>

    <ul id="knjige" class="knjige"></ul>

    <nav id="strane" class="strane" aria-label="Strane" hidden>
        <button type="button" data-nazad>← Prethodna</button>
        <span data-strana></span>
        <button type="button" data-napred>Sledeća →</button>
    </nav>

    <template id="sablon-knjiga">
        <li class="knjiga">
            <h2><button type="button" class="link" data-detalji><span data-naslov></span></button></h2>
            <p data-autori class="autori"></p>
            <p><strong data-cena></strong> · <span data-stanje></span></p>
            <button type="button" data-u-korpu>U korpu</button>
        </li>
    </template>

    <dialog id="detalji">
        <h2></h2>
        <div data-sadrzaj></div>
        <form method="dialog"><button>Zatvori</button></form>
    </dialog>

    <dialog id="korpa">
        <h2>Korpa</h2>
        <ul></ul>
        <p>Ukupno: <strong data-ukupno></strong></p>
        <p class="napomena">Poručivanje je dostupno posle prijave (glava 11).</p>
        <form method="dialog"><button>Zatvori</button></form>
    </dialog>
</x-shop>
