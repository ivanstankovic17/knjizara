/**
 * Forma za novu knjigu: provera u pregledaču pre slanja, a posle slanja
 * prikaz grešaka koje je vratio server (422) uz odgovarajuća polja.
 */
import { api } from './api.js';
import { isValidIsbn13 } from './isbn.js';

const form = document.querySelector('#nova-knjiga');
const result = document.querySelector('#rezultat');

// Poruke na srpskom za ugrađene provere pregledača (Constraint Validation API).
function messageFor(field) {
    const v = field.validity;
    if (v.valueMissing) return field.multiple ? 'Izaberite bar jednog autora.' : 'Polje je obavezno.';
    if (v.typeMismatch || v.badInput) return 'Vrednost nije ispravna.';
    if (v.rangeUnderflow) return `Najmanja dozvoljena vrednost je ${field.min}.`;
    if (v.rangeOverflow) return `Najveća dozvoljena vrednost je ${field.max}.`;
    if (v.stepMismatch) return 'Vrednost mora biti ceo broj.';
    if (v.tooLong) return `Najviše ${field.maxLength} znakova.`;
    if (v.customError) return field.validationMessage;
    return '';
}

function showError(name, message) {
    const field = form.elements[name];
    const output = form.querySelector(`[data-greska="${name}"]`);
    if (!field || !output) return false;
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
    output.textContent = message;
    return true;
}

function clearErrors() {
    for (const output of form.querySelectorAll('[data-greska]')) {
        showError(output.dataset.greska, '');
    }
}

// Sopstvena provera: ISBN sa ispravnom kontrolnom cifrom.
form.elements.isbn.addEventListener('input', (e) => {
    const value = e.target.value.trim();
    e.target.setCustomValidity(value && !isValidIsbn13(value)
        ? 'ISBN nije ispravan: proverite cifre (kontrolna cifra se ne slaže).'
        : '');
});

// Poruka se prikazuje kada korisnik napusti polje, a ne dok kuca.
form.addEventListener('focusout', (e) => {
    if (e.target.name && e.target.willValidate) {
        showError(e.target.name, messageFor(e.target));
    }
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    result.textContent = '';

    // 1. Provera u pregledaču: ako nešto ne valja, zahtev se ne šalje.
    const invalid = [...form.elements].filter((f) => f.willValidate && !f.checkValidity());
    if (invalid.length > 0) {
        invalid.forEach((f) => showError(f.name, messageFor(f)));
        invalid[0].focus();
        return;
    }

    const data = new FormData(form);
    const book = {
        title: data.get('title'),
        isbn: data.get('isbn'),
        price: Number(data.get('price')),
        stock: Number(data.get('stock') || 0),
        published_year: data.get('published_year') ? Number(data.get('published_year')) : null,
        category_id: Number(data.get('category_id')),
        author_ids: data.getAll('author_ids').map(Number),
        description: data.get('description') || null,
    };

    // 2. Provera na serveru: ona je obavezna i može da nađe ono što
    //    pregledač ne zna (ISBN već postoji, odeljak 8.1).
    const button = form.querySelector('[type=submit]');
    button.disabled = true;
    try {
        const { data: created } = await api('/books', { method: 'POST', body: book });
        form.reset();
        result.textContent = `Knjiga „${created.title}” je dodata (id ${created.id}).`;
    } catch (error) {
        if (error.status === 422) {
            for (const [key, messages] of Object.entries(error.errors)) {
                // "author_ids.2" => polje "author_ids"
                showError(key.split('.')[0], messages.join(' '));
            }
            form.querySelector('[aria-invalid=true]')?.focus();
        } else {
            result.textContent = `${error.title}: ${error.message}`
                + (error.requestId ? ` (broj zahteva ${error.requestId})` : '');
        }
    } finally {
        button.disabled = false;
    }
});

// Kategorije i autori za padajuće liste.
async function loadOptions() {
    const [categories, authors] = await Promise.all([
        api('/categories'),
        api('/authors?per_page=50'),
    ]);
    form.elements.category_id.append(...categories.data.map((c) => new Option(c.name, c.id)));
    form.elements.author_ids.append(...authors.data.map((a) => new Option(a.name, a.id)));
}

loadOptions().catch((e) => {
    result.textContent = `Spiskovi nisu učitani: ${e.message}`;
});
