/**
 * Katalog knjižare: spisak knjiga sa pretragom, filterima, sortiranjem i
 * stranicama, detalji knjige i korpa. Stanje filtera je u adresi stranice,
 * pa se može podeliti, sačuvati i vratiti dugmetom „nazad”.
 */
import { api, query } from './api.js';
import { cart } from './cart.js';
import { formatAuthors, formatPrice } from './format.js';

const $ = (selector) => document.querySelector(selector);

const form = $('#filteri');
const list = $('#knjige');
const status = $('#status');
const pager = $('#strane');
const bookTemplate = $('#sablon-knjiga');
const details = $('#detalji');
const cartDialog = $('#korpa');

const FILTERS = ['q', 'category', 'sort', 'min_price', 'max_price', 'in_stock', 'page'];

let controller = null; // zahtev za spisak koji je u toku

// ---------------------------------------------------------------- spisak

async function loadBooks() {
    controller?.abort(); // stariji zahtev više nije potreban
    controller = new AbortController();

    const params = Object.fromEntries(new URLSearchParams(location.search));
    showStatus('Učitavanje…');
    list.setAttribute('aria-busy', 'true');

    try {
        const page = await api(`/books?${query({ ...params, include: 'authors', per_page: 12 })}`, {
            signal: controller.signal,
        });
        renderBooks(page);
    } catch (e) {
        if (e.name === 'AbortError') {
            return; // stigao je noviji zahtev
        }
        renderError(e);
    } finally {
        list.removeAttribute('aria-busy');
    }
}

function renderBooks({ data, meta }) {
    list.replaceChildren(...data.map(renderBook));

    if (data.length === 0) {
        showStatus('Nema knjiga koje odgovaraju pretrazi.');
    } else {
        showStatus(`Pronađeno knjiga: ${meta.total}.`);
    }

    pager.hidden = meta.last_page <= 1;
    pager.querySelector('[data-strana]').textContent = `Strana ${meta.current_page} od ${meta.last_page}`;
    pager.querySelector('[data-nazad]').disabled = meta.current_page <= 1;
    pager.querySelector('[data-napred]').disabled = meta.current_page >= meta.last_page;
    pager.dataset.page = meta.current_page;
}

function renderBook(book) {
    const item = bookTemplate.content.firstElementChild.cloneNode(true);

    // textContent, a ne innerHTML: naslov dolazi sa servera i ne sme
    // da se tumači kao HTML (XSS, odeljak 4.6 i glava 13).
    item.querySelector('[data-naslov]').textContent = book.title;
    item.querySelector('[data-autori]').textContent = formatAuthors(book.authors);
    item.querySelector('[data-cena]').textContent = formatPrice(book.price);
    item.querySelector('[data-stanje]').textContent = book.in_stock ? 'Na stanju' : 'Nema na stanju';
    item.classList.toggle('nema', !book.in_stock);

    item.querySelector('[data-detalji]').addEventListener('click', () => showDetails(book.id));
    const add = item.querySelector('[data-u-korpu]');
    add.disabled = !book.in_stock;
    add.addEventListener('click', () => cart.add(book));

    return item;
}

function renderError(error) {
    list.replaceChildren();
    pager.hidden = true;

    // Za 422 (neispravan filter) navodimo sve poruke i označavamo polja.
    const errors = Object.entries(error.errors ?? {});
    for (const [field] of errors) {
        form.elements[field]?.setAttribute('aria-invalid', 'true');
    }
    const detail = errors.length > 0 ? errors.flatMap(([, m]) => m).join(' ') : error.message;

    const message = document.createElement('p');
    message.className = 'greska';
    message.textContent = `${error.title}. ${detail}`;

    const retry = document.createElement('button');
    retry.type = 'button';
    retry.textContent = 'Pokušaj ponovo';
    retry.addEventListener('click', loadBooks);

    status.replaceChildren(message, retry);
}

function showStatus(text) {
    status.textContent = text;
}

// --------------------------------------------------------------- filteri

/** Upisuje vrednosti iz adrese u formu (pri učitavanju i dugmetom „nazad”). */
function fillForm() {
    const params = new URLSearchParams(location.search);
    for (const name of FILTERS) {
        const field = form.elements[name];
        if (!field) continue;
        if (field.type === 'checkbox') {
            field.checked = params.get(name) === '1';
        } else if (field.tagName === 'SELECT') {
            field.value = params.get(name) ?? field.options[0].value;
        } else {
            field.value = params.get(name) ?? '';
        }
        field.removeAttribute('aria-invalid');
    }
}

/** Prenosi formu u adresu stranice i učitava spisak. */
function applyFilters({ page = 1 } = {}) {
    const values = {};
    for (const name of FILTERS) {
        const field = form.elements[name];
        if (field) {
            values[name] = field.type === 'checkbox' ? (field.checked ? '1' : '') : field.value.trim();
        }
    }
    values.page = page > 1 ? page : '';
    if (values.sort === 'title') values.sort = ''; // podrazumevano

    const search = query(values);
    history.pushState(null, '', search ? `?${search}` : location.pathname);
    loadBooks();
}

let typing; // odlaganje pretrage dok korisnik kuca
form.elements.q.addEventListener('input', () => {
    clearTimeout(typing);
    typing = setTimeout(applyFilters, 300);
});

form.addEventListener('change', (e) => {
    if (e.target.name !== 'q') applyFilters();
});

form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(typing);
    applyFilters();
});

pager.querySelector('[data-nazad]').addEventListener('click', () =>
    applyFilters({ page: Number(pager.dataset.page) - 1 }));
pager.querySelector('[data-napred]').addEventListener('click', () =>
    applyFilters({ page: Number(pager.dataset.page) + 1 }));

window.addEventListener('popstate', () => {
    fillForm();
    loadBooks();
});

async function loadCategories() {
    const select = form.elements.category;
    try {
        const { data } = await api('/categories');
        select.append(...data.map((c) => new Option(`${c.name} (${c.books_count})`, c.slug)));
        select.value = new URLSearchParams(location.search).get('category') ?? '';
    } catch {
        select.disabled = true; // spisak knjiga radi i bez kategorija
    }
}

// --------------------------------------------------------------- detalji

async function showDetails(id) {
    const body = details.querySelector('[data-sadrzaj]');
    body.textContent = 'Učitavanje…';
    details.showModal();

    try {
        const { data: book } = await api(`/books/${id}`);
        details.querySelector('h2').textContent = book.title;
        const rows = [
            ['Autori', formatAuthors(book.authors)],
            ['Kategorija', book.category.name],
            ['Godina izdanja', book.published_year ?? '—'],
            ['ISBN', book.isbn],
            ['Cena', formatPrice(book.price)],
        ];
        const dl = document.createElement('dl');
        for (const [term, value] of rows) {
            dl.append(Object.assign(document.createElement('dt'), { textContent: term }));
            dl.append(Object.assign(document.createElement('dd'), { textContent: value }));
        }
        const description = Object.assign(document.createElement('p'), {
            textContent: book.description ?? '',
        });
        body.replaceChildren(dl, description);
    } catch (e) {
        details.querySelector('h2').textContent = 'Greška';
        body.textContent = e.message;
    }
}

// ----------------------------------------------------------------- korpa

function renderCart() {
    $('#broj-u-korpi').textContent = cart.count();

    const rows = cart.items().map((item) => {
        const li = document.createElement('li');
        li.textContent = `${item.title} × ${item.quantity} = ${formatPrice(item.price * item.quantity)} `;
        const remove = Object.assign(document.createElement('button'), {
            type: 'button',
            textContent: 'Ukloni',
        });
        remove.addEventListener('click', () => cart.remove(item.id));
        li.append(remove);
        return li;
    });
    cartDialog.querySelector('ul').replaceChildren(...rows);
    cartDialog.querySelector('[data-ukupno]').textContent = formatPrice(cart.total());
}

$('#otvori-korpu').addEventListener('click', () => cartDialog.showModal());
document.addEventListener('korpa:promena', renderCart);

// ---------------------------------------------------------------- početak

fillForm();
renderCart();
loadCategories();
loadBooks();
