# 0004. Tuđa porudžbina je 404, ne 403

Status: prihvaćeno (glava 12 knjige)

## Kontekst

`403` za tuđu porudžbinu potvrđuje da porudžbina sa tim brojem postoji, pa se brojevi mogu nabrajati (IDOR).

## Odluka

`OrderPolicy` odbija sa `denyAsNotFound`. Tuđa i nepostojeća porudžbina daju isti odgovor.

## Posledice

Klijent ne može da razlikuje „nema dozvole” od „ne postoji”, i to je cilj. Scramble mora da dobije `#[IgnoreResponse(403)]`.
