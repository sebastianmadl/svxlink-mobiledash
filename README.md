# SVXLink Mobile Dashboard

Modernes, schnelles und mobiloptimiertes Dashboard für SVXLink.

Dieses Projekt erweitert das originale SVXLink Dashboard um eine vollständig mobile Oberfläche mit Fokus auf einfache Bedienung, Stabilität und schnelle Steuerung direkt vom Smartphone.

Es verwendet die bestehende Dashboard-Installation und spiegelt deren Konfiguration automatisch.

---

## Screenshot

![SVXLink Mobile Dashboard](images/screenshot.png)

---

## Features

- 📱 Vollständig mobiloptimiertes Interface
- 🎛️ DTMF-Tastatur mit vollständiger Unterstützung
- 🔘 Schnellbefehle werden automatisch aus der originalen `config.php` übernommen
- 📡 Reflector- und Modulstatus in Echtzeit
- 🔴 Live TX-Anzeige
- ⚡ Sehr ressourcenschonend
- 🔧 100 % kompatibel mit dem originalen SVXLink Dashboard
- 🚫 Keine Änderungen an SVXLink erforderlich
- 🌐 Zugriff von jedem Smartphone oder Tablet

---

## Automatische Übernahme der Dashboard-Konfiguration

Das Mobile Dashboard verwendet direkt die bestehende Konfiguration des originalen Dashboards:

/var/www/html/include/config.php

Alle dort definierten DTMF-Befehle, Schnellwahltasten und Talkgroup-Definitionen werden automatisch übernommen und im Mobile Dashboard gespiegelt.

Das bedeutet:

- keine doppelte Konfiguration
- keine zusätzlichen Einstellungen notwendig
- Änderungen im originalen Dashboard erscheinen automatisch im Mobile Dashboard

---

## Voraussetzungen

- Installiertes und funktionierendes SVXLink
- Installiertes originales SVXLink Dashboard
- Webserver (Apache empfohlen)
- Dashboard installiert unter:

/var/www/html

---

## Installation

### Direkt von GitHub installieren

```bash
wget https://github.com/sebastianmadl/svxlink-mobiledash/raw/main/svxlink-mobiledash.zip && sudo unzip -o svxlink-mobiledash.zip -d /var/www/html

Oder manuell

sudo unzip -o svxlink-mobiledash.zip -d /var/www/html


⸻

Zugriff

Nach Installation erreichbar unter:

http://<server-ip>/mobile

Beispiel:

http://192.168.1.123/mobile


⸻

Funktionsweise

Das Mobile Dashboard nutzt das originale SVXLink Dashboard Backend zur Steuerung:

mobile → api/dtmf.php → include/buttons.php → SVXLink

Dadurch wird exakt dieselbe Logik und Berechtigung wie im originalen Dashboard verwendet.

Dies garantiert maximale Stabilität und Kompatibilität.

⸻

Projektstruktur

/var/www/html/
 ├── include/
 │   └── config.php
 └── mobile/
     ├── index.php
     ├── css/
     ├── js/
     ├── api/
     └── images/


⸻

Kompatibilität

Getestet mit:
	•	SVXLink Dashboard V2 (DL3EL / SP2ONG)
	•	Debian / Ubuntu

⸻

Autor

OE1SXM — Sebastian M.

GitHub:
https://github.com/sebastianmadl

⸻

Lizenz

Dieses Projekt erweitert das originale SVXLink Dashboard um eine mobile Benutzeroberfläche.

Verwendung auf eigene Verantwortung.

⸻

Feedback und Beiträge

Issues und Pull Requests sind willkommen:

https://github.com/sebastianmadl/svxlink-mobiledash

---

# GitHub Release Beschreibung (für Release v1.0)

Titel:

SVXLink Mobile Dashboard v1.0

Beschreibung:

```markdown
Initial Release des SVXLink Mobile Dashboards.

Dieses Projekt erweitert das originale SVXLink Dashboard um eine moderne, mobile Oberfläche für Smartphones und Tablets.

Features:

- vollständig mobiloptimiertes Interface
- funktionierende DTMF-Steuerung
- automatische Übernahme aller Schnellwahltasten aus config.php
- Reflector- und Modulstatusanzeige
- Live TX-Anzeige
- keine Änderungen an SVXLink erforderlich
- vollständig kompatibel mit dem originalen Dashboard

Installation:

```bash
wget https://github.com/sebastianmadl/svxlink-mobiledash/raw/main/svxlink-mobiledash.zip && sudo unzip -o svxlink-mobiledash.zip -d /var/www/html

Zugriff:

http://<server-ip>/mobile

Autor: OE1SXM

---
