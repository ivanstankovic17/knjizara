/**
 * Pozivi API-ja knjižare: JSON u oba smera, greške kao ApiError
 * (format RFC 9457 iz glave 8), sa vremenskim ograničenjem.
 */

const BASE = '/api/v1';

export class ApiError extends Error {
    constructor({ status, type = 'about:blank', title, detail, errors = {}, request_id = null }) {
        super(detail ?? title);
        this.name = 'ApiError';
        this.status = status;
        this.type = type;
        this.title = title;
        this.errors = errors;
        this.requestId = request_id;
    }
}

/**
 * @param {string} path  putanja posle /api/v1, na primer '/books?page=2'
 * @param {{method?: string, body?: unknown, signal?: AbortSignal, timeout?: number}} options
 */
export async function api(path, { method = 'GET', body, signal, timeout = 10_000 } = {}) {
    const headers = { Accept: 'application/json' };
    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const signals = [AbortSignal.timeout(timeout)];
    if (signal) {
        signals.push(signal);
    }

    let response;
    try {
        response = await fetch(BASE + path, {
            method,
            headers,
            body: body === undefined ? undefined : JSON.stringify(body),
            signal: AbortSignal.any(signals),
        });
    } catch (e) {
        if (e.name === 'AbortError') {
            throw e; // zahtev je namerno prekinut, to nije greška
        }
        const timedOut = e.name === 'TimeoutError';
        throw new ApiError({
            status: 0,
            type: timedOut ? 'timeout' : 'network',
            title: timedOut ? 'Server ne odgovara' : 'Nema veze sa serverom',
            detail: timedOut
                ? `Odgovor nije stigao za ${timeout / 1000} s.`
                : 'Proverite internet vezu i pokušajte ponovo.',
        });
    }

    if (response.status === 204) {
        return null;
    }

    const type = response.headers.get('Content-Type') ?? '';
    const data = type.includes('json') ? await response.json() : null;

    if (!response.ok) {
        throw new ApiError(data ?? { status: response.status, title: response.statusText });
    }

    return data;
}

/**
 * {q: 'kuća', page: 2, in_stock: ''} => 'q=ku%C4%87a&page=2' (prazne vrednosti se izostavljaju)
 */
export function query(params) {
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value !== '' && value !== null && value !== undefined) {
            search.set(key, value);
        }
    }
    return search.toString();
}
