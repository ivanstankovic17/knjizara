import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
import { xsrfToken } from './api.js';
window.Pusher = Pusher;

// Bez ključa (frontend izgrađen bez podešavanja Reverba, kao u CI-ju,
// odeljak 16.6) stranice rade bez obaveštenja u realnom vremenu:
// window.Echo ostaje nedefinisan, a stranice ga koriste sa ?. (glava 10).
if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],

        // Dozvola za privatni kanal: zahtev ide sa kolačićem sesije i CSRF
        // tokenom, a server odgovara potpisom koji proverava Reverb (glava 11).
        authorizer: (channel) => ({
            authorize: async (socketId, callback) => {
                try {
                    const response = await fetch('/broadcasting/auth', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': xsrfToken(),
                        },
                        body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                    });
                    if (!response.ok) {
                        throw new Error(`Pretplata nije dozvoljena (${response.status}).`);
                    }
                    callback(null, await response.json());
                } catch (error) {
                    callback(error, null);
                }
            },
        }),
    });
}
