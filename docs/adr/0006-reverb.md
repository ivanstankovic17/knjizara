# 0006. Reverb za obaveštenja u realnom vremenu

Status: prihvaćeno (glava 10 knjige)

## Kontekst

Kupac treba da vidi promenu statusa porudžbine bez osvežavanja. Ponavljano ispitivanje servera (*polling*) troši zahteve iz ograničenja od 60 u minuti.

## Odluka

Laravel Reverb (WebSocket, Pusher protokol), privatni kanal `korisnik.{id}`, iza nginx-a na `/app`.

## Posledice

Još jedan dugotrajan proces. Bez ključa za Reverb stranice rade, ali bez obaveštenja (`echo.js`, glava 16).
