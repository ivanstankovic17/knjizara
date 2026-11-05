/**
 * Zajedničko za forme naloga: slanje na API i prikaz grešaka 422 uz
 * polja, kao u formi za novu knjigu (odeljak 9.7).
 */
import { api } from './api.js';

export function clearErrors(form) {
    for (const output of form.querySelectorAll('[data-greska]')) {
        output.textContent = '';
        form.elements[output.dataset.greska]?.removeAttribute('aria-invalid');
    }
    form.querySelector('.status').textContent = '';
}

export function showErrors(form, error) {
    const status = form.querySelector('.status');
    if (error.status !== 422) {
        status.textContent = error.message;
        return;
    }
    for (const [key, messages] of Object.entries(error.errors)) {
        const name = key.split('.')[0];
        const output = form.querySelector(`[data-greska="${name}"]`);
        if (output) {
            output.textContent = messages.join(' ');
            form.elements[name]?.setAttribute('aria-invalid', 'true');
        } else {
            status.textContent += ` ${messages.join(' ')}`;
        }
    }
    form.querySelector('[aria-invalid=true]')?.focus();
}

/**
 * Šalje formu kao JSON na zadatu putanju; vraća telo odgovora ili null
 * ako je bilo grešaka (koje su već prikazane).
 */
export async function submitForm(form, path) {
    clearErrors(form);
    const button = form.querySelector('[type=submit]');
    button.disabled = true;
    try {
        const body = Object.fromEntries(new FormData(form));
        return await api(path, { method: 'POST', body });
    } catch (error) {
        showErrors(form, error);
        return null;
    } finally {
        button.disabled = false;
    }
}
