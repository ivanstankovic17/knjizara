# 0009. Docker Compose za produkciju na jednom serveru

Status: prihvaćeno (glava 16 knjige)

## Kontekst

Knjižara ima sedam procesa (nginx, PHP-FPM, radnik, planer, Reverb, MySQL, Redis). Ručna instalacija na serveru se ne može ponoviti ni proveriti.

## Odluka

Jedna slika aplikacije u četiri kontejnera, nginx i servisi u `compose.yaml`. Konfiguracija iz `.env.docker`, keš konfiguracije pri pokretanju kontejnera.

## Posledice

Isto okruženje na laptopu, u CI-ju i na serveru. Za više servera treba orkestrator (Kubernetes) i planer na samo jednom (`onOneServer`).
