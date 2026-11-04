/**
 * Korpa u localStorage pregledača. Na server ide tek pri poručivanju
 * (glava 11), pa ovde nema poziva API-ja.
 */

const KEY = 'knjizara.korpa';

function read() {
    try {
        return JSON.parse(localStorage.getItem(KEY)) ?? {};
    } catch {
        return {}; // oštećen ili ručno izmenjen sadržaj
    }
}

function write(items) {
    localStorage.setItem(KEY, JSON.stringify(items));
    document.dispatchEvent(new CustomEvent('korpa:promena', { detail: items }));
}

export const cart = {
    items: () => Object.values(read()),

    add(book) {
        const items = read();
        const item = items[book.id] ?? { id: book.id, title: book.title, price: book.price, quantity: 0 };
        item.quantity += 1;
        items[book.id] = item;
        write(items);
    },

    remove(id) {
        const items = read();
        delete items[id];
        write(items);
    },

    count: () => cart.items().reduce((sum, i) => sum + i.quantity, 0),

    total: () => cart.items().reduce((sum, i) => sum + i.price * i.quantity, 0),
};

// Promena u drugoj kartici pregledača stiže kao događaj storage.
window.addEventListener('storage', (e) => {
    if (e.key === KEY) {
        document.dispatchEvent(new CustomEvent('korpa:promena', { detail: read() }));
    }
});
