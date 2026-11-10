import { defineConfig } from '@playwright/test';

// End-to-end testovi (odeljak 14.9): pravi pregledač, pravi server,
// posebna baza (.env.e2e). Pokretanje: npx playwright test
export default defineConfig({
    testDir: 'tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL: 'http://localhost:8001',
        // Lokalno Edge koji je već instaliran; na CI serveru (odeljak 16.6)
        // Playwright-ov Chromium, koji radni tok instalira.
        channel: process.env.CI ? undefined : 'msedge',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --port=8001',
        env: { APP_ENV: 'e2e' },
        url: 'http://localhost:8001/up',
        reuseExistingServer: true,
    },
});
