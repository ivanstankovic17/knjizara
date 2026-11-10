# Knjižara

Internet knjižara iz knjige *Napredne web tehnologije*: REST API u Laravelu 13 i JavaScript klijent koji ga koristi. Projekat raste kroz knjigu, glavu po glavu, a ovde je u stanju sa kraja glave 15.

- Katalog knjiga, autora i kategorija, sa pretragom, filterima, sortiranjem i paginacijom.
- Porudžbine: transakcija koja skida knjige sa stanja, otkazivanje i statusi.
- Nalozi: registracija sa potvrdom adrese, prijava sesijom (stranice knjižare) i tokenom (ostali klijenti), prijava preko GitHub-a, dvofaktorska prijava.
- Uloge kupac, menadžer i administrator, sa dozvolama.
- Red poslova i keš u Redisu, obaveštenja u realnom vremenu preko Reverba.
- Greške u formatu RFC 9457 i OpenAPI dokumentacija generisana iz koda.

## Potrebno

- PHP 8.3 ili noviji, sa ekstenzijom `pdo_sqlite`
- Composer 2
- Node.js 22.12 ili noviji
- Redis; najlakše kao Docker kontejner (ispod)

Baza je SQLite, u fajlu `database/database.sqlite`, pa poseban server baze nije potreban.

## Prvo pokretanje

Keš i red poslova su u Redisu, pa on mora da radi pre pokretanja aplikacije:

```bash
docker run -d --name knjizara-redis -p 6379:6379 --restart unless-stopped redis:7-alpine
```

Zatim:

```bash
git clone https://github.com/ivanstankovic17/knjizara.git
cd knjizara
composer setup
```

Komanda `composer setup` instalira PHP i JavaScript pakete, pravi `.env` iz `.env.example`, generiše `APP_KEY` i ključeve za Reverb, pravi bazu sa probnim podacima i gradi frontend. Pokreće se samo jednom, na novom klonu.

## Svakodnevni rad

```bash
composer dev
```

Pokreće sve što je potrebno, u jednom terminalu: server aplikacije na <http://localhost:8000>, Reverb na portu 8080, obradu reda poslova i Vite, koji osvežava stranicu posle izmene JavaScript-a ili CSS-a.

Probni nalozi, oba sa lozinkom `password`:

| Nalog | Uloga |
|---|---|
| `ana@knjizara.test` | administrator |
| `marko@knjizara.test` | menadžer |

Kupca napravite registracijom na <http://localhost:8000/prijava>. Mejlovi se ne šalju, nego upisuju u `storage/logs/laravel.log` (`MAIL_MAILER=log`), pa se odatle čita i link za potvrdu adrese.

Prijava preko GitHub-a traži OAuth aplikaciju na GitHub-u i vrednosti `GITHUB_CLIENT_ID` i `GITHUB_CLIENT_SECRET` u `.env` (odeljak 11.6 knjige). Bez njih ostali delovi rade normalno.

## Dokumentacija API-ja

- <http://localhost:8000/docs/api>: Swagger UI, sa opisom svake operacije i dugmetom *Try it out*
- <http://localhost:8000/docs/api.json>: OpenAPI 3.1 specifikacija, za alate i generisanje klijenata
- [`docs/api-v1.md`](docs/api-v1.md): ugovor API-ja, tabele operacija i statusnih kodova
- [`bruno/`](bruno): kolekcija zahteva za [Bruno](https://www.usebruno.com)

Specifikaciju generiše [Scramble](https://scramble.dedoc.co) iz ruta, kontrolera, validacije i resursa, pri svakom zahtevu. Test `tests/Feature/OpenApiContractTest.php` proverava da se slaže sa ugovorom.

## Testovi i provere

```bash
./vendor/bin/pest                                # svi testovi
./vendor/bin/pest --exclude-group=dokumentacija  # bez sporijih testova specifikacije
./vendor/bin/phpstan analyse                     # statička analiza (Larastan, nivo 5)
./vendor/bin/pint --test                         # stil koda
```

Testovi koriste SQLite bazu u memoriji, pa ne diraju razvojne podatke.

Bruno scenario i end-to-end testovi rade sa posebnim serverom na portu 8001 i posebnom bazom. Okruženje se pravi jednom:

```bash
cp .env.e2e.example .env.e2e
php artisan key:generate --env=e2e
```

Posle toga:

```bash
npx playwright test                        # E2E u pregledaču Edge
APP_ENV=e2e php artisan serve --port=8001  # server za Bruno, u posebnom terminalu
cd bruno && bru run Kupovina --env e2e     # scenario kupovine
```

Bruno CLI se instalira globalno, van projekta: `npm install -g @usebruno/cli`. Bruno scenario očekuje bazu koju je napravio `npx playwright test`, ili komandu `APP_ENV=e2e php artisan migrate:fresh --seeder=E2eSeeder --force`.

## Produkcija u Docker-u

Ceo sistem (nginx, PHP-FPM, red poslova, planer, Reverb, MySQL, Redis) podiže se sa `docker compose`. Lokalno radi na <https://localhost:8443>, sa samopotpisanim sertifikatom:

```bash
cp .env.production.example .env.production   # upišite APP_KEY, lozinku baze i ključeve za Reverb
mkdir -p docker/nginx/certs
openssl req -x509 -newkey rsa:2048 -nodes -days 365 -subj "/CN=localhost" \
    -keyout docker/nginx/certs/knjizara.key -out docker/nginx/certs/knjizara.crt
docker compose --env-file .env.production up -d --build
docker compose --env-file .env.production exec app php artisan migrate --force
docker compose --env-file .env.production exec app php artisan db:seed --class=CategorySeeder --force
docker compose --env-file .env.production exec app php artisan knjizara:admin ana@knjizara.test "Ana Petrović"
```

Na pravom serveru sertifikat izdaje Let's Encrypt, a `APP_URL` i `WEBSOCKET_ORIGIN` sadrže pravi domen (glava 16 knjige). GitHub Actions (`.github/workflows/ci.yml`) za svaki push pokreće stil, Larastan, testove na SQLite-u i MySQL-u, E2E testove i izgradnju slike.

## Struktura

| Fascikla | Šta je u njoj |
|---|---|
| `app/Http/Controllers/Api/V1` | kontroleri API-ja |
| `app/Actions` | poslovne operacije (porudžbina, otkazivanje, provera prijave) |
| `app/Http/Resources` | JSON reprezentacije resursa |
| `app/Policies`, `app/Enums/Permission.php` | autorizacija |
| `app/OpenApi` | dopune za Scramble: format greške, zajednički odgovori |
| `resources/js` | JavaScript klijent, po jedan modul za svaku stranicu |
| `routes/api.php`, `routes/web.php` | rute API-ja i stranica |
| `tests/Unit`, `tests/Feature`, `tests/e2e` | testovi |

## Licenca

MIT.
