# 0007. OpenAPI se generiše iz koda, ugovor se piše ručno

Status: prihvaćeno (glava 15 knjige)

## Kontekst

Ručno pisana specifikacija zastareva, a potpuno generisana ne kaže šta je obećano klijentima.

## Odluka

Scramble pravi specifikaciju iz koda, sa dopunama u `app/OpenApi`. Ugovor je `docs/api-v1.md`, a test `OpenApiContractTest` poredi oba.

## Posledice

Svaka nova ruta traži red u ugovoru, inače test pada. Generisanje traje nekoliko sekundi, pa je test u posebnoj grupi.
