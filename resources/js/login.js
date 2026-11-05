/**
 * Prijava, registracija, zaboravljena i nova lozinka (glava 11).
 * Prijava sesijom: posle uspeha pregledač ima kolačić sesije, pa ga
 * šalje uz svaki sledeći zahtev ka API-ju i stranicama.
 */
import { submitForm } from './forms.js';

const $ = (selector) => document.querySelector(selector);

// Posle prijave nazad tamo odakle je korisnik došao (?nazad=/katalog).
const back = () => new URLSearchParams(location.search).get('nazad') ?? '/nalog';

$('#prijava')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const user = await submitForm(form, '/auth/login');
    if (user) {
        location.assign(back());
    } else if (form.querySelector('[data-greska="code"]').textContent) {
        // Server traži kod druge faze: prikaži polje za kod.
        form.querySelector('[data-kod]').hidden = false;
        form.elements.code.focus();
    }
});

$('#registracija')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (await submitForm(e.target, '/auth/register')) {
        location.assign('/nalog');
    }
});

$('#zaboravljena')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await submitForm(e.target, '/auth/forgot-password');
    if (result) {
        e.target.querySelector('.status').textContent = result.message;
    }
});

$('#nova-lozinka')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await submitForm(e.target, '/auth/reset-password');
    if (result) {
        e.target.querySelector('.status').textContent = result.message;
        setTimeout(() => location.assign('/prijava'), 2000);
    }
});
