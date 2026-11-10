# 0008. SQLite za razvoj i testove, MySQL za produkciju

Status: prihvaćeno (glave 5, 14, 16 knjige)

## Kontekst

SQLite ne traži server i testovi sa bazom u memoriji su brzi. Produkcija treba bazu za više istovremenih korisnika.

## Odluka

SQLite lokalno i u testovima, MySQL 8.4 na produkciji. CI pokreće sve testove na obe baze.

## Posledice

Razlike baza su stvarne: `sum()` na MySQL-u vraća string, a brojač `AUTO_INCREMENT` se ne vraća posle testa (odeljak 16.1). Bez testova na MySQL-u otkrili bi ih korisnici.
