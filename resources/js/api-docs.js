/**
 * Swagger UI za API knjižare (odeljak 15.4). Skript i stil dolaze iz
 * npm paketa preko Vite-a, sa našeg domena, pa stranica radi uz CSP
 * iz glave 13, bez CDN-a i bez skripti u HTML-u.
 */
// Gotov paket iz swagger-ui-dist; glavni modul paketa je za Node.
import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';
import { xsrfToken } from './api';

SwaggerUIBundle({
    url: '/docs/api.json',
    dom_id: '#swagger',
    // Bez provere specifikacije na validator.swagger.io: CSP to ne dozvoljava,
    // a specifikacija ne treba da napušta naš server.
    validatorUrl: null,
    deepLinking: true,
    docExpansion: 'list',
    defaultModelsExpandDepth: 0,
    tryItOutEnabled: false,
    // "Try it out" sa ove stranice ide na isti domen, pa Sanctum koristi
    // sesiju: zahtevima koji menjaju podatke treba i CSRF token.
    requestInterceptor: (request) => {
        request.headers['X-XSRF-TOKEN'] = xsrfToken();
        return request;
    },
});
