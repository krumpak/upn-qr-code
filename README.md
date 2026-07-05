# upn-qr-code

## API dokumentacija

Generiranje UPN QR kode.

### Zahtevek

```
POST /generate.php
Content-Type: application/json
```

Telo zahtevka je JSON objekt z naslednjimi polji:

| Polje | Obvezno | Pravila / oblika | Opis |
|---|---|---|---|
| `placilo_znesek` | ✅ | `^\d+\.\d{2}$`, večji od `0` | Znesek v EUR z dvema decimalkama (npr. `10.00`). Pretvori se v 11-mestni zapis v centih. |
| `placilo_datum` | ✅ | oblika `d.m.Y`, ne v preteklosti | Rok plačila (npr. `31.12.2026`). |
| `placilo_koda_namena` | ✅ | — | 4-črkovna koda namena (npr. `GDSV`). |
| `placilo_namen` | ✅ | — | Namen plačila (prosti tekst). |
| `placilo_referenca_oznaka` | ✅ | — | Oznaka reference (npr. `SI`). Normalizira se v velike črke. |
| `placilo_referenca_model` | ✅ | — | Model reference (npr. `00`). |
| `placilo_referenca_sklic` | ✅ | `^[A-Z0-9\-]+$` | Sklic — dovoljene le črke, številke in vezaji (`-`). |
| `placnik_naziv` | ✅ | — | Naziv plačnika. |
| `placnik_naslov` | ➖ | — | Naslov plačnika. |
| `placnik_kraj` | ➖ | — | Kraj plačnika. |
| `prejemnik_naziv` | ✅ | — | Naziv prejemnika. |
| `prejemnik_naslov` | ➖ | — | Naslov prejemnika. |
| `prejemnik_kraj` | ➖ | — | Kraj prejemnika. |
| `prejemnik_iban` | ✅ | `^SI\d{17}$` | IBAN prejemnika. Presledki se odstranijo, črke se pretvorijo v velike (`SI56 0203 ...` → `SI56020360...`). |

> **Referenca:** polje `placilo_referenca` se **ne** pošilja — backend jo sam sestavi iz
> `placilo_referenca_oznaka` + `placilo_referenca_model` + `placilo_referenca_sklic`
> (npr. `SI` + `00` + `12345` = `SI0012345`). Morebitna poslana vrednost je ignorirana.

### Primer zahtevka

```json
{
  "placilo_znesek": "10.00",
  "placilo_datum": "31.12.2026",
  "placilo_koda_namena": "GDSV",
  "placilo_namen": "Plačilo računa",
  "placilo_referenca_oznaka": "SI",
  "placilo_referenca_model": "00",
  "placilo_referenca_sklic": "12345",
  "placnik_naziv": "Janez Novak",
  "placnik_naslov": "Ulica 1",
  "placnik_kraj": "1000 Ljubljana",
  "prejemnik_naziv": "Podjetje d.o.o.",
  "prejemnik_naslov": "Cesta 2",
  "prejemnik_kraj": "2000 Maribor",
  "prejemnik_iban": "SI56 0203 6025 3863 406"
}
```

### Odgovori

| Status | Telo | Pomen |
|---|---|---|
| `200` | `{ "qr": "...", "raw": "..." }` | Uspeh. `qr` je PNG QR koda kot `data:`-URI, `raw` je generiran UPN payload (vrstice ločene z `\n` + 3-mestna kontrolna vsota). |
| `400` | `{ "error": "Neveljaven JSON." }` | Telo zahtevka je prazno ali ni veljaven JSON. |
| `422` | `{ "errors": { "<polje>": ["<sporočilo>", ...] } }` | Napake pri validaciji. Ključ je ime polja, vrednost pa seznam sporočil. |

> **Opomba:** napake za `placilo_referenca_model` in `placilo_referenca_sklic` se
> vrnejo pod ključem `placilo_referenca_oznaka`.

### Primer uspešnega odgovora (200)

```json
{
  "qr": "data:image/png;base64,iVBORw0KGgo...",
  "raw": "UPNQR\n\n\n\n\nJanez Novak\n...\n123"
}
```

### Testi

```
make test
```

Zažene lokalni PHP strežnik in izvede `test/test.php`.

---

### Deploy

```
make deploy
```

### Xdebug 3

1) `php -v` preveri PHP verzijo
2) `brew install php-xdebug` namesti Xdebu
3) `php -m | grep xdebug` preveri ali obstaja Xdebug
4) `php --ini` poišči .ini datoteko in dodaj
```
zend_extension="/opt/homebrew/lib/php/pecl/20250925/xdebug.so"

xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```
5) Namesti VScode vtičnik `PHP Debug` (by Xdebug)
6) V VScode odpri `Run & Debug` sidebar in poženi  `create launch.json`
```
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003
    }
  ]
}
```
