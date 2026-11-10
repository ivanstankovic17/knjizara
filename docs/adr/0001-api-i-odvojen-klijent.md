# 0001. REST API i odvojen JavaScript klijent

Status: prihvaćeno (glave 1, 6, 9 knjige)

## Kontekst

Knjižaru koriste stranice u pregledaču, a planirani su i mobilna aplikacija i spoljni partneri. Server koji pravi HTML (Blade) bio bi jednostavniji za prve stranice, ali bi za svakog novog klijenta trebalo praviti drugi interfejs.

## Odluka

Sva logika je iza REST API-ja `/api/v1`. Stranice su tanke Blade ljuske, a podatke dobijaju od API-ja iz JavaScript modula (Vite).

## Posledice

Isti API služi pregledaču, testovima, Brunu i budućim klijentima. Cena je više koda na klijentu i dva načina prijave (ADR 0002). SEO za katalog bi zahtevao i HTML sa servera.
