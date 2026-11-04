<x-shop title="Nova knjiga" script="resources/js/new-book.js">
    <form id="nova-knjiga" class="forma" novalidate>
        <label for="title">Naslov</label>
        <input id="title" name="title" required maxlength="255" aria-describedby="g-title">
        <p class="greska" id="g-title" data-greska="title"></p>

        <label for="isbn">ISBN</label>
        <input id="isbn" name="isbn" required inputmode="numeric" placeholder="978-86-..." aria-describedby="g-isbn">
        <p class="greska" id="g-isbn" data-greska="isbn"></p>

        <label for="price">Cena (dinara)</label>
        <input id="price" name="price" type="number" required min="1" max="1000000" step="1" aria-describedby="g-price">
        <p class="greska" id="g-price" data-greska="price"></p>

        <label for="stock">Na stanju (primeraka)</label>
        <input id="stock" name="stock" type="number" min="0" step="1" value="0" aria-describedby="g-stock">
        <p class="greska" id="g-stock" data-greska="stock"></p>

        <label for="published_year">Godina izdanja</label>
        <input id="published_year" name="published_year" type="number" min="1450" max="{{ now()->year }}" step="1"
               aria-describedby="g-published_year">
        <p class="greska" id="g-published_year" data-greska="published_year"></p>

        <label for="category_id">Kategorija</label>
        <select id="category_id" name="category_id" required aria-describedby="g-category_id">
            <option value="">izaberite</option>
        </select>
        <p class="greska" id="g-category_id" data-greska="category_id"></p>

        <label for="author_ids">Autori (Ctrl + klik za više)</label>
        <select id="author_ids" name="author_ids" multiple required size="6" aria-describedby="g-author_ids"></select>
        <p class="greska" id="g-author_ids" data-greska="author_ids"></p>

        <label for="description">Opis</label>
        <textarea id="description" name="description" rows="4" maxlength="5000" aria-describedby="g-description"></textarea>
        <p class="greska" id="g-description" data-greska="description"></p>

        <button type="submit">Sačuvaj knjigu</button>
        <p id="rezultat" role="status" aria-live="polite"></p>
    </form>
</x-shop>
