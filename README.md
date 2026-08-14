# STB Atelier – ATELIER HAIR TATTOO (Multi-Page Website)

HTML / CSS / JS / PHP. Eigene Seiten statt One-Pager. Aufbau, Sektionen,
Services, Reviews, Kontakt und Navigation orientieren sich an stbatelier.ch.
Bilder und längere Originaltexte sind Platzhalter (siehe unten).

## Seiten
| Datei            | Inhalt                                                        |
|------------------|---------------------------------------------------------------|
| `index.html`     | Start (Hero, New Services, About, Team, Hair, Tattoo, Services, Best Sellers, Reviews, Rewards, Kontakt) |
| `about-us.html`  | About Us + Meet our Team                                       |
| `services.html`  | Services (Beards, Combinations, Facial + Pakete)              |
| `tattoo.html`    | Tattoo-Galerien (Realistic, Old School/Neo Traditional, Lettering) |
| `beauty.html`    | **NEU** – Beauty (Laser Diodo, Massages, Facial Cleansing)    |
| `products.html`  | Products (Best Sellers, Urban, Treat)                         |

## Technik
| Datei            | Zweck                                                         |
|------------------|---------------------------------------------------------------|
| `stylesheet.css` | Gemeinsames Styling                                           |
| `script.js`      | Mobile-Menü, aktiver Nav-Link je Seite, Reveal, Kontaktformular |
| `contact.php`    | Nimmt das Kontaktformular entgegen, speichert + mailt         |
| `admin.php`      | Passwortgeschütztes Dashboard für Kontaktanfragen             |
| `config.php`     | Konfiguration (liest Login/Empfänger-Mail aus config.local.php bzw. Umgebungsvariablen, siehe unten) |
| `config.local.php.example` | Vorlage für lokale Zugangsdaten – kopieren nach `config.local.php` (nicht eingecheckt) |

## Buchung
Es gibt **kein eigenes Reservierungssystem**. Alle „Book now" / „Booking" /
„Book on Treatwell" Buttons verlinken direkt auf Treatwell:
- Salon: https://www.treatwell.ch/ort/stb-atelier-hair-tattoo/
- Booking: https://buchung.treatwell.ch/ort/stbarber/

Das Kontaktformular ist nur ein Kontaktformular (keine Buchung).

## Lokal starten
PHP nötig für Formular + Admin:
```bash
php -S localhost:8000
```
- Website:  http://localhost:8000/index.html
- Admin:    http://localhost:8000/admin.php

Reines Design-Anschauen geht auch ohne PHP (HTML direkt öffnen).

## Admin-Zugang einrichten
Es gibt **keinen eingebauten Default-Login** mehr – ohne Einrichtung bleibt
`admin.php` gesperrt. Zugangsdaten werden **nicht** im Code gespeichert,
sondern entweder über eine lokale, nicht eingecheckte Datei oder über
Umgebungsvariablen bereitgestellt (siehe `config.php`).

**Variante A – lokale Datei (empfohlen für eigenes Hosting):**
```bash
cp config.local.php.example config.local.php
php -r "echo password_hash('DEIN_PASSWORT', PASSWORD_DEFAULT);"
```
Den ausgegebenen Hash zusammen mit dem gewünschten Benutzernamen in
`config.local.php` eintragen. Diese Datei ist in `.gitignore` und wird nie
committed.

**Variante B – Umgebungsvariablen** (z. B. wenn das Hosting kein Anlegen
lokaler Dateien erlaubt): `STB_ADMIN_USER` und `STB_ADMIN_HASH` setzen.

⚠️ Falls das alte Default-Passwort (`stb-admin-2026`) jemals produktiv im
Einsatz war: als kompromittiert behandeln und nicht wiederverwenden – es
stand zuvor im Klartext-Code und in dieser README.

## Sicherheit im Kontaktformular
- Verstecktes Honeypot-Feld (`website`) filtert einfache Bots.
- Rate-Limit von 30 Sekunden pro IP-Adresse gegen Formular-Flooding.
- Löschen im Admin-Dashboard ist per CSRF-Token abgesichert.
- `data/` (Kontaktanfragen) und `config.local.php` (Zugangsdaten) sind über
  `.gitignore` von Commits ausgeschlossen.

## Logo
Aktuell ein Platzhalter unter `assets/img/logo.svg`. Euer echtes Logo einfach
als `assets/img/logo.svg` **oder** `assets/img/logo.png` ablegen und im Header
`src="assets/img/logo.svg"` entsprechend anpassen. Höhe wird über CSS
(`.logo-img { height:60px }`) gesteuert.

## Bilder & Texte
- Bilder: graue Platzhalter zeigen den vorgesehenen Dateinamen
  (z. B. `assets/img/about-salon.jpg`). Echte Bilder in `assets/img/` ablegen
  und den `<div class="ph" ...>` durch `<img src="...">` ersetzen.
- Texte: an den mit `ORIGINALTEXT EINFÜGEN` markierten Stellen (About / Hair)
  den Originaltext einsetzen. Alle Fakten (Namen, Rollen, Services, Zeiten,
  Reviews, Produktnamen, Adresse) sind bereits drin.

## Beauty-Sektion
Eigene Seite `beauty.html`. Übernimmt vorerst die bestehende Farbwelt
(anthrazit + gold), mit farbigen Karten-Akzenten. Sobald ihr die definitiven
Beauty-Farben habt, passe ich `--*`-Variablen bzw. die `.beauty-*` Klassen an.

## Offene Punkte für 1:1
- Exakter Gold-Hex und ggf. die exakte Schriftart (aktuell Playfair Display /
  EB Garamond / Jost) – wenn ihr die Werte habt, ziehe ich nach.
- Echtes Logo-Bild.
