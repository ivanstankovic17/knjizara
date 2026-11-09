# API knjižare, verzija 1

Ugovor API-ja knjižare, verzija 1 (knjiga, glava 6). Ugovor važi za katalog i porudžbine. Registraciju i prijavu dodajemo u glavi 11, a upravljanje korisnicima u glavi 12. Sve adrese su relativne u odnosu na `/api/v1`. Svi zahtevi šalju `Accept: application/json`, a zahtevi sa telom i `Content-Type: application/json`.

## Krajnje tačke API-ja knjižare, verzija 1

| Zahtev | Ko sme | Uspeh | Greške |
|---|---|---|---|
| `GET /books` | svi | `200`, stranica knjiga | `422` |
| `GET /books/{id}` | svi | `200`, knjiga | `404` |
| `POST /books` | menadžer | `201` + `Location` | `401`, `403`, `415`, `422` |
| `PATCH /books/{id}` | menadžer | `200`, izmenjena knjiga | `401`, `403`, `404`, `415`, `422` |
| `DELETE /books/{id}` | menadžer | `204` | `401`, `403`, `404`, `409` |
| `GET /authors` | svi | `200`, stranica autora | `422` |
| `GET /authors/{id}` | svi | `200`, autor | `404` |
| `GET /authors/{id}/books` | svi | `200`, stranica knjiga autora | `404`, `422` |
| `GET /categories` | svi | `200` + `ETag`, sve kategorije, bez paginacije | `304` za uslovni zahtev |
| `GET /categories/{slug}` | svi | `200`, kategorija | `404` |
| `POST /orders` | kupac | `201` + `Location` | `401`, `409` (nema na stanju), `422` |
| `GET /orders` | kupac | `200`, stranica *sopstvenih* porudžbina | `401` |
| `GET /orders/{id}` | vlasnik, menadžer | `200`, porudžbina sa stavkama | `401`, `404` |
| `POST /orders/{id}/cancel` | vlasnik, menadžer | `200`, otkazana porudžbina | `401`, `404`, `409` (nije `pending`) |
| `POST /reports/sales` | menadžer | `202` + `Location` | `401`, `403` |
| `GET /reports/{id}` | menadžer | `200`, status izveštaja | `401`, `403`, `404` |
| `GET /admin/orders` | menadžer | `200`, stranica svih porudžbina, sa kupcem; filter `status` | `401`, `403`, `422` |
| `PATCH /admin/orders/{id}` | menadžer | `200`, porudžbina sa novim statusom (`paid`, `shipped`) | `401`, `403`, `404`, `409` (nedozvoljen prelaz), `422` |
| `GET /admin/users` | administrator | `200`, stranica korisnika; filter `role` | `401`, `403`, `422` |
| `PATCH /admin/users/{id}` | administrator | `200`, korisnik sa novom ulogom | `401`, `403` (sopstvena uloga), `404`, `422` |

Uloge i dozvole (glava 12): menadžer ima `catalog.manage`, `orders.manage` i `reports.view`, a administrator i `users.manage`. Prijavljenom korisniku resursi vraćaju i polje `can` sa akcijama koje sme da izvede, na primer `"can": {"update": true, "delete": true}` za knjigu ili `"can": {"cancel": false}` za porudžbinu. Porudžbina za menadžera ima i polje `customer`.

Kategorije nemaju paginaciju, jer ih je malo, a klijent ih prikazuje sve odjednom u meniju. Zato mogu dugo da se keširaju. Autori i knjige autora imaju istu paginaciju kao knjige. Kupac koji traži tuđu porudžbinu dobija `404`, kao da porudžbina ne postoji. Nedostatak knjiga na stanju je `409`, a ne `422`. Zahtev je ispravan i mogao bi da uspe kada knjiga ponovo bude na stanju.

## Parametri spiska knjiga

| Parametar | Primer | Značenje |
|---|---|---|
| `q` | `q=andrić` | Tekst u naslovu ili u imenu autora |
| `category` | `category=roman` | Slug kategorije |
| `author` | `author=7,12` | Bar jedan od navedenih autora |
| `min_price`, `max_price` | `min_price=1000` | Cena u dinarima, uključujući granice |
| `in_stock` | `in_stock=1` | Samo knjige na stanju |
| `sort` | `sort=-price,title` | Polja `title`, `price`, `published_year`, `created_at`; minus za opadajući redosled; podrazumevano `title` |
| `include` | `include=authors,category` | Ugrađeni povezani resursi |
| `page`, `per_page` | `page=2&per_page=20` | Broj strane od 1; veličina od 1 do 50, podrazumevano 15 |

Nepoznat parametar, nepoznato polje za `sort` ili `include`, i vrednost pogrešnog tipa vraćaju `422`, sa porukom koja kaže šta nije u redu.

Reprezentacije knjige i porudžbine:

```json
{
    "id": 1,
    "title": "Tišina iz detinjstva",
    "slug": "tisina-iz-detinjstva-9693",
    "isbn": "9797995466607",
    "description": "...",
    "price": 1390,
    "in_stock": true,
    "published_year": 1961,
    "category": { "id": 1, "name": "Roman", "slug": "roman" },
    "authors": [{ "id": 17, "name": "Ristivoje Sibinčić" }],
    "created_at": "2026-10-29T16:27:53+00:00",
    "updated_at": "2026-10-29T16:27:53+00:00"
}
```

```json
{
    "id": 1041,
    "status": "pending",
    "total": 2780,
    "items": [
        { "book": { "id": 1, "title": "Tišina iz detinjstva" },
          "quantity": 2, "unit_price": 1390 }
    ],
    "created_at": "2026-10-29T18:02:44+00:00"
}
```

U spisku knjiga polja `category` i `authors` postoje samo ako su tražena parametrom `include`. U pojedinačnoj knjizi uvek postoje. Menadžer dobija i polje `stock`. Telo za pravljenje porudžbine sadrži samo knjige i količine. Cene, ukupan iznos i status određuje server:

```json
{ "items": [{ "book_id": 1, "quantity": 2 }, { "book_id": 22, "quantity": 1 }] }
```

## Format greške

Sve greške imaju telo u formatu RFC 9457 (`application/problem+json`), sa poljima `type`, `title`, `status`, `detail` i `instance`. Greške validacije dodaju i spisak grešaka po poljima (glava 8).
