import { execFileSync } from 'node:child_process';

// Pre svih testova: sveža baza sa početnim podacima i kupcem Petrom.
export default function globalSetup() {
    execFileSync('php',
        ['artisan', 'migrate:fresh', '--seeder=E2eSeeder', '--force'],
        { env: { ...process.env, APP_ENV: 'e2e' }, stdio: 'ignore' });
}
