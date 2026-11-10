# 0005. Redis za keš, sesije i red poslova

Status: prihvaćeno (glava 10 knjige)

## Kontekst

Slanje mejla i izveštaji ne smeju da usporavaju odgovor. Red u bazi bi opterećivao istu bazu koja služi katalog.

## Odluka

Redis (klijent predis, bez PHP ekstenzije), za keš, sesije na produkciji i red poslova. Radnik `queue:work` radi u posebnom kontejneru.

## Posledice

Još jedan servis za održavanje. Obaveštenje stiže sa zakašnjenjem od jedne do tri sekunde, koliko radnik čeka između provera reda.
