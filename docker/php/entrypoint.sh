#!/bin/sh
# Pokreće se pri svakom startu kontejnera (app, queue, scheduler, reverb).
# Keš konfiguracije se pravi ovde, a ne u slici: konfiguracija dolazi iz
# promenljivih okruženja kontejnera, koje pri izgradnji slike ne postoje.
set -e
php artisan optimize
exec "$@"
