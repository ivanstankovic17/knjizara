/**
 * Porudžbine svih kupaca, za menadžera (glava 12). Dugmad prate
 * dozvoljene prelaze statusa; server ih proverava i sam (409).
 */
import { api } from './api.js';
import { formatPrice } from './format.js';

const $ = (selector) => document.querySelector(selector);

const STATUS = {
    pending: 'na čekanju',
    paid: 'plaćena',
    shipped: 'poslata',
    cancelled: 'otkazana',
};

// Isti prelazi kao OrderStatus::canBecome na serveru.
const NEXT = { pending: 'paid', paid: 'shipped' };
const ACTION = { paid: 'Plaćena', shipped: 'Poslata' };

function button(text, onClick) {
    const b = Object.assign(document.createElement('button'), { type: 'button', textContent: text });
    b.addEventListener('click', onClick);
    return b;
}

function renderOrder(order) {
    const li = document.createElement('li');
    li.dataset.id = order.id;

    const title = document.createElement('strong');
    title.textContent = `Porudžbina ${order.id}`;
    const status = document.createElement('span');
    status.dataset.status = order.status;
    status.textContent = STATUS[order.status] ?? order.status;

    li.append(title, ' · ', order.customer.name, ' · ', status, ' · ', formatPrice(order.total));

    const next = NEXT[order.status];
    if (next) {
        li.append(' ', button(ACTION[next], () => change(li, () =>
            api(`/admin/orders/${order.id}`, { method: 'PATCH', body: { status: next } }))));
    }
    if (order.can.cancel) {
        li.append(' ', button('Otkaži', () => change(li, () =>
            api(`/orders/${order.id}/cancel`, { method: 'POST' }))));
    }

    return li;
}

/** Izmena jedne porudžbine; posle nje se red osvežava podacima sa servera. */
async function change(li, request) {
    try {
        const { data } = await request();
        const updated = renderOrder(data);
        updated.classList.add('promenjeno');
        li.replaceWith(updated);
        $('#status').textContent = `Porudžbina ${data.id} je sada: ${STATUS[data.status]}.`;
    } catch (e) {
        $('#status').textContent = e.message;
    }
}

async function load() {
    const status = new FormData($('#filter')).get('status');
    $('#status').textContent = 'Učitavanje…';
    try {
        const query = status ? `?status=${status}` : '';
        const { data, meta } = await api(`/admin/orders${query}`);
        $('#porudzbine').replaceChildren(...data.map(renderOrder));
        $('#status').textContent = meta.total === 0 ? 'Nema porudžbina.' : `Ukupno: ${meta.total}`;
    } catch (e) {
        $('#status').textContent = e.message;
    }
}

$('#filter').addEventListener('change', load);
load();
