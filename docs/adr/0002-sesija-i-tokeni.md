# 0002. Sanctum: sesija za stranice, tokeni za ostale

Status: prihvaćeno (glava 11 knjige)

## Kontekst

Stranice knjižare su na istom domenu kao API, a ostali klijenti nisu. JWT je razmatran i odbačen: ne može se opozvati pre isteka bez dodatne tabele (odeljak 11.3).

## Odluka

Laravel Sanctum. Stranice se prijavljuju sesijom (kolačić `httponly`, CSRF zaštita), ostali klijenti dobijaju neproziran token sa rokom od 30 dana, koji se čuva kao heš u bazi.

## Posledice

Token se opoziva jednim brisanjem reda. Dva toka prijave znače i dva skupa testova. `POST /auth/login` odbija klijente bez sesije (`400`).
