# upn-qr-code

## API dokumentacija

Generiranje UPN QR kode.

### Zahtevek

```
POST /generate.php
Content-Type: application/json
```

Telo zahtevka je JSON objekt z naslednjimi polji:

| Polje | Obvezno | Pravila / oblika | Max | Opis |
|---|---|---|---|---|
| `placilo_znesek` | ✅ | `^\d+\.\d{2}$`, večji od `0`, največ `999999999.99` | — | Znesek v EUR z dvema decimalkama (npr. `10.00`). Pretvori se v 11-mestni zapis v centih (zato zgornja meja 999.999.999,99 EUR). |
| `placilo_datum` | ✅ | oblika `d.m.Y` (`DD.MM.YYYY`), ne v preteklosti | — | Rok plačila (npr. `31.12.2026`). |
| `placilo_koda_namena` | ✅ | `^[A-Z]{4}$` | 4 | Koda namena — 4 velike črke A-Z (npr. `GDSV`). |
| `placilo_namen` | ✅ | — | 42 | Namen plačila (prosti tekst). |
| `placilo_referenca_oznaka` | ✅ | `SI` ali `RF` | 2 | Oznaka reference. Normalizira se v velike črke. |
| `placilo_referenca_model` | ✅ | `^\d{2}$` | 2 | Model reference (npr. `00`). |
| `placilo_referenca_sklic` | ➖¹ | `^[A-Z0-9\-]+$` | 22 | Sklic — dovoljene le črke, številke in vezaji (`-`). ¹Obvezen, razen pri modelu `99`. |
| `placnik_naziv` | ✅ | — | 33 | Naziv plačnika. (Standard ga ne zahteva, a je pri nas obvezen.) |
| `placnik_naslov` | ➖ | — | 33 | Naslov plačnika. |
| `placnik_kraj` | ➖ | — | 33 | Kraj plačnika. |
| `prejemnik_naziv` | ✅ | — | 33 | Naziv prejemnika. |
| `prejemnik_naslov` | ✅ | — | 33 | Naslov prejemnika. |
| `prejemnik_kraj` | ✅ | — | 33 | Kraj prejemnika. |
| `prejemnik_iban` | ✅ | `^SI\d{17}$` | — | IBAN prejemnika. Presledki se odstranijo, črke se pretvorijo v velike (`SI56 0203 ...` → `SI56020360...`). |

> **Globalno (vsa besedilna polja):** vodilni in sledeči presledki se odstranijo (trim);
> dovoljeni so le znaki iz nabora **ISO-8859-2** (v tem naboru se kodira QR).
>
> **Dolžine in obveznost polj** sledijo tehničnemu standardu UPN QR (ZBS) — glej [Viri](#viri).
> Izjema po naši izbiri: `placnik_naziv` je obvezen (standard ga ne zahteva).

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

---

## Viri

Pravila validacije (dolžine polj, obveznost, oblike) sledijo uradnemu tehničnemu standardu
UPN QR Združenja bank Slovenije (ZBS):

- [EN Tehnični standard UPN QR (ZBS, PDF)](https://www.zbs-giz.si/wp-content/uploads/2021/10/EN_Tehnicni_standard_UPN_QR.pdf)
- [Standardi in priročniki (ZBS-GIZ)](https://www.zbs-giz.si/standardi/)
- [Tehnični standard UPN QR (upn-qr.si)](https://upn-qr.si/sl/tehnicni-standard)
- [Navodilo za pripravo izpisa UPN QR za programerje (ZBS, PDF)](https://www.simple-shop.si/bt/index.php?getfile=270)
- [UPN QR – navodila za izpolnjevanje (Racunovodja.com)](https://www.racunovodja.com/clanki.asp?clanek=9691)
