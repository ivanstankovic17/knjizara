# 0003. Greške u formatu RFC 9457

Status: prihvaćeno (glava 8 knjige)

## Kontekst

Laravel greške vraća u nekoliko oblika (validacija, 404, 500). Klijenti su morali da prepoznaju svaki posebno.

## Odluka

Sve greške API-ja imaju oblik `application/problem+json` (`type`, `title`, `status`, `detail`, `instance`, `request_id`). Sukobi sa stanjem podataka su `409`, izvedeni iz `ConflictException`.

## Posledice

Klijent ima jednu funkciju za greške. Specifikacija API-ja mora da se dopuni (ADR 0007), jer alati podrazumevaju Laravelov oblik.
