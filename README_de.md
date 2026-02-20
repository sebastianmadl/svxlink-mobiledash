# 📡 SVXLink Mobile Dashboard Ver 1.1 <img src="mobile/images/icon-512.png" width="40">

🇬🇧 [English version of this README](README.md)

Ein smartphone-optimiertes mobiles Frontend für SVXLink Reflector Nodes. Ursprünglich als Erweiterung für [SVXLink-Dash-V2 von DL3EL](https://github.com/DL3EL/SVXLink-Dash-V2) entwickelt — jetzt auch vollständig kompatibel mit dem originalen Dashboard von [SP2ONG und SP0DZ](https://github.com/SP2ONG/SVXLink-Dashboard).

<p align="center">
  <img src="/images/dashboard.png" width="350">
  <img src="/images/activity.png" width="350">
  <img src="/images/talkgroups.png" width="350">
  <img src="/images/dtmf.png" width="350">
</p>

-----

## ✨ Features

### 🖥️ Dashboard & Live Daten
- 📊 **Live Dashboard** — Radiostatus, aktive TGs und aktueller Sprecher via SSE
- 📋 **Aktivitätslog** — SVXReflector Aktivität in Echtzeit
- 🔁 **Talk Groups** — Anzeigen, per Tipp aktivieren oder TG-Nummer manuell eingeben
- ⌨️ **DTMF Tastatur** — DTMF-Töne mit einem Tipp senden
- ⚡ **Schnellbefehle** — Konfigurierbare Shortcut-Buttons (aus `include/config.php`)

### 🎨 Erscheinungsbild
- 🌙☀️⚙️ **Dark / Light / System Theme** — Manuell wählen oder Systemeinstellung folgen
- 🌐 **Zweisprachige Oberfläche** — Englisch 🇬🇧 und Deutsch 🇩🇪 — jederzeit ohne Neuladen wechselbar

### ⚙️ Konfiguration & Setup
- 🚀 **Erststart-Assistent** — Sprach- und Theme-Auswahl beim ersten Start, kein manuelles Konfigurieren nötig
- ⚙️ **Einstellungsmenü** — Auf den Verbindungs-Badge (oben rechts) tippen um Sprache & Theme zu ändern
- 🔧 **Portable Installation** — Keine hardcodierten Pfade, funktioniert neben jeder bestehenden Dashboard-Installation
- ⌨️ **DTMF** — Sendet Töne direkt nach `/tmp/dtmf_svx` — kompatibel mit SP2ONG/SP0DZ und DL3EL Dashboards

### 📱 Mobile
- 📱 **Als PWA installierbar** — Vollbild-App-Erlebnis auf iOS & Android

-----

## 📲 Installation

> **Hinweis:** Die folgenden Befehle setzen `/var/www/html` als Web-Verzeichnis voraus — der Standard für [SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2) und das [SP2ONG/SP0DZ Dashboard](https://github.com/SP2ONG/SVXLink-Dashboard). Bei einem anderen Web-Verzeichnis den Pfad im Befehl entsprechend anpassen.

In das Web-Verzeichnis wechseln:
```bash
cd /var/www/html
```

Klonen und installieren:
```bash
sudo git clone --depth 1 https://github.com/sebastianmadl/svxlink-mobiledash.git temp && sudo mv temp/mobile mobile && sudo rm -rf temp && sudo chown -R www-data:www-data mobile && sudo chmod 666 mobile/configs/mobile_settings.json
```

Dann im Browser öffnen:
```
http://<IP-des-SvxHosts>/mobile/
```

Beim ersten Start führt der Einrichtungsassistent durch die Sprach- und Theme-Auswahl.

-----

## 🔄 Update von Ver 1.0

> **Hinweis:** Vor dem Update in das Web-Verzeichnis der Dashboard-Installation wechseln — in den meisten Fällen `/var/www/html`.
```bash
cd /var/www/html
```

Alte Installation entfernen:
```bash
sudo rm -rf mobile
```

Danach den [Installationsschritten](#-installation) oben folgen.

> **Hinweis:** Dabei werden Sprache und Theme zurückgesetzt. Der Einrichtungsassistent wird beim nächsten Start erneut angezeigt.

-----

## 📱 Als App installieren (PWA)

Das Dashboard kann auf dem Homescreen installiert werden und verhält sich wie eine native App — kein Browser-Balken, Vollbild.

<img src="mobile/images/icon-512.png" width="100">

**iOS (Safari):**
1. Seite in Safari öffnen
2. Teilen-Symbol tippen (⬆️)
3. „Zum Home-Bildschirm" wählen
4. Bestätigen → „Hinzufügen"

**Android (Chrome):**
1. Seite in Chrome öffnen
2. Menü tippen (⋮)
3. „Zum Startbildschirm hinzufügen" wählen
4. Bestätigen → „Hinzufügen"

> Auf iOS muss Safari verwendet werden. Auf Android funktionieren auch Firefox oder Edge.

-----

## 🖌️ Dark / Light Mode

Dark, Light oder System-Theme beim Erststart-Assistenten wählen oder jederzeit über das Einstellungsmenü ändern — auf den Verbindungs-Badge oben rechts tippen.

<p align="center">
  <img src="/images/dark.png" width="350">
  <img src="/images/light.png" width="350">
</p>

-----

## 📁 Verzeichnisstruktur
```
/var/www/html/
├── include/
│   └── config.php                # Gelesen von configs/config.php → SVX Pfade (SVXCONFPATH, SVXLOGPATH etc.)
├── images/
│   ├── favicon.ico               # Verwendet von index.php, setup.php
│   └── svxlink.ico               # Verwendet von index.php (Header Logo)
├── index.php                     # Verlinkt von mobile index.php (↗ Vollständiges Dashboard)
│
└── mobile/
    ├── index.php                 # Haupt-SPA (EN + DE, einzelne Datei)
    ├── setup.php                 # Welcome Page (nur beim Erststart)
    ├── settings.php              # AJAX Handler – speichert Sprache & Theme
    ├── manifest.json             # PWA Manifest
    ├── configs/
    │   ├── config.php            # Zentrale Konfiguration – liest ../include/config.php (SVX Pfade)
    │   │                         #                        – liest mobile_config.php (App Metadaten)
    │   │                         #                        – liest mobile_settings.json (Sprache, Theme)
    │   ├── mobile_config.php     # App Metadaten (Version, Jahr, Autor)
    │   └── mobile_settings.json  # Benutzereinstellungen (Sprache, Theme) ← chmod 666
    ├── css/
    │   └── app.css               # Gemeinsame Styles inkl. Dark/Light/System Theme
    ├── js/
    │   └── app.js                # Frontend Logik – Live Sprach- & Theme-Wechsel
    ├── api/
    │   ├── dtmf.php              # DTMF Endpoint – schreibt Code nach /tmp/dtmf_svx
    │   │                         #               – kompatibel mit SP2ONG/SP0DZ und DL3EL
    │   └── stream.php            # SSE Stream – liest Log von SVXLOGPATH/SVXLOGPREFIX
    │                             #            – liest SVX Pfade via configs/config.php
    └── images/
        ├── icon-192.png          # PWA Icon (klein)
        └── icon-512.png          # PWA Icon (groß)
```

-----

## 📋 Changelog

### Ver 1.1 — 2026-02-20
- **Neue Struktur** — `en/` Unterordner entfernt, einzelne `index.php` für beide Sprachen
- **`configs/` Ordner** — `config.php` und `mobile_config.php` in eigenen Unterordner verschoben
- **`mobile_settings.json`** — Sprache & Theme werden jetzt als JSON gespeichert statt in PHP-defines
- **`setup.php`** — neue Welcome Page beim Erststart mit Sprach- & Theme-Auswahl inkl. Live-Vorschau
- **`settings.php`** — AJAX Handler der `mobile_settings.json` schreibt
- **Live-Wechsel** — Sprache & Theme wechseln ohne Seitenreload via `data-i18n` und `CFG.allStrings`
- **Einstellungsmenü** — Verbindungs-Badge (oben rechts) öffnet das Einstellungs-Modal
- **`api/dtmf.php`** — vereinfacht, schreibt direkt nach `/tmp/dtmf_svx`, kompatibel mit SP2ONG/SP0DZ und DL3EL

-----

## 🙏 Credits

- [DL3EL/SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2)
- [SP2ONG/SVXLink-Dashboard](https://github.com/SP2ONG/SVXLink-Dashboard)
- [sm0svx/svxlink](https://github.com/sm0svx/svxlink)

-----

## 👤 Autor

**OE1SXM — Sebastian M.**  
[github.com/sebastianmadl](https://github.com/sebastianmadl)

-----

## 📄 Lizenz

Dieses Projekt basiert auf SVXLink-Dash-V2. Lizenzdetails sind im Originalprojekt zu finden.

README_de.md übersetzt von OpenAI, übersetztungsfehler vorbehalten.
