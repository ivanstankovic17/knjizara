/**
 * Stranica „Moj nalog”: porudžbine sa promenom statusa u realnom
 * vremenu (privatni kanal, odeljak 10.7), dvofaktorska prijava, odjava.
 */
import './echo.js';
import { api } from './api.js';
import { formatPrice } from './format.js';

const $ = (selector) => document.querySelector(selector);
const userId = document.body.dataset.korisnik;

const STATUS = {
    pending: 'na čekanju',
    paid: 'plaćena',
    shipped: 'poslata',
    cancelled: 'otkazana',
};

// ------------------------------------------------------------ porudžbine

function renderOrder(order) {
    const li = document.createElement('li');
    li.dataset.id = order.id;

    const title = document.createElement('strong');
    title.textContent = `Porudžbina ${order.id}`;
    const status = document.createElement('span');
    status.dataset.status = order.status;
    status.textContent = STATUS[order.status] ?? order.status;
    const total = document.createElement('span');
    total.textContent = formatPrice(order.total);

    li.append(title, ' · ', status, ' · ', total);

    // Server kaže da li ovaj korisnik sme da otkaže porudžbinu (glava 12).
    if (order.can.cancel) {
        const cancel = Object.assign(document.createElement('button'), {
            type: 'button',
            textContent: 'Otkaži',
        });
        cancel.addEventListener('click', () => cancelOrder(order.id, li));
        li.append(' ', cancel);
    }

    return li;
}

async function loadOrders() {
    const status = $('#status-porudzbina');
    status.textContent = 'Učitavanje…';
    try {
        const { data, meta } = await api('/orders');
        $('#porudzbine').replaceChildren(...data.map(renderOrder));
        status.textContent = meta.total === 0 ? 'Još nemate porudžbina.' : '';
    } catch (e) {
        status.textContent = e.message;
    }
}

async function cancelOrder(id, li) {
    try {
        const { data } = await api(`/orders/${id}/cancel`, { method: 'POST' });
        li.replaceWith(renderOrder(data));
    } catch (e) {
        $('#status-porudzbina').textContent = e.message;
    }
}

/** Status je promenjen na serveru (menadžer, otkazivanje u drugoj kartici). */
function onStatusChanged({ id, status, total }) {
    const li = $(`#porudzbine [data-id="${id}"]`);
    const updated = renderOrder({
        id, status, total,
        // Poruka sa kanala nema polje can; vlasnik sme da otkaže dok čeka.
        can: { cancel: status === 'pending' },
    });
    updated.classList.add('promenjeno');
    li ? li.replaceWith(updated) : $('#porudzbine').prepend(updated);
    $('#status-porudzbina').textContent =
        `Porudžbina ${id} je sada: ${STATUS[status] ?? status}.`;
}

// Privatni kanal: Echo prvo pita /broadcasting/auth da li korisnik sme
// da ga sluša, a server proverava pravilo iz routes/channels.php.
window.Echo?.private(`korisnik.${userId}`)
    .listen('.porudzbina.status', onStatusChanged);

// ----------------------------------------------------- dvofaktorska prijava

const twoFactor = $('#dva-faktora');

function renderTwoFactor() {
    const on = twoFactor.dataset.ukljuceno === '1';
    twoFactor.querySelector('[data-stanje]').textContent = on
        ? 'Uključena: pri prijavi se traži i kod iz aplikacije.'
        : 'Isključena: prijava traži samo lozinku.';
    twoFactor.querySelector('[data-dugme]').textContent = on ? 'Isključi' : 'Uključi';
}

twoFactor.querySelector('[data-dugme]').addEventListener('click', async (e) => {
    const error = twoFactor.querySelector('[data-greska]');
    const setup = twoFactor.querySelector('[data-podesavanje]');
    error.textContent = '';
    try {
        if (twoFactor.dataset.ukljuceno === '1') {
            const password = prompt('Za isključivanje unesite lozinku:');
            if (!password) return;
            await api('/auth/two-factor', { method: 'DELETE', body: { password } });
            twoFactor.dataset.ukljuceno = '0';
        } else if (setup.hidden) {
            const { secret } = await api('/auth/two-factor', { method: 'POST' });
            twoFactor.querySelector('[data-tajna]').textContent = secret;
            setup.hidden = false;
            e.target.textContent = 'Potvrdi kod';
            return;
        } else {
            const code = twoFactor.querySelector('[name=code]').value.trim();
            await api('/auth/two-factor/confirm', { method: 'POST', body: { code } });
            setup.hidden = true;
            twoFactor.dataset.ukljuceno = '1';
        }
        renderTwoFactor();
    } catch (err) {
        error.textContent = Object.values(err.errors ?? {}).flat().join(' ') || err.message;
    }
});

// ----------------------------------------------------------------- ostalo

$('#odjava').addEventListener('click', async () => {
    await api('/auth/logout', { method: 'POST' });
    location.assign('/katalog');
});

$('#ponovo-posalji')?.addEventListener('click', async (e) => {
    await api('/auth/email/verification-notification', { method: 'POST' });
    e.target.replaceWith('Poslato. Proverite poštu.');
});

renderTwoFactor();
loadOrders();
