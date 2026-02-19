# 📡 SVXLink Mobile Dashboard Ver 1.0

Eine Erweiterung um die mobil funktion des DL3EL Dashboards.
Aufgebaut auf Basis von [SVXLink-Dash-V2 von DL3EL](https://github.com/DL3EL/SVXLink-Dash-V2). Wird hier das Gesammte desktop Dashboard mobil verpackt und aufgeräumt.


<p align="center">
  <img src="/images/dark.png" width="350">
  <img src="/images/activity.png" width="350">
  <img src="/images/talkgroups.png" width="350">
  <img src="/images/dtmf.png" width="350">
</p>

-----

## ✨ Funktionen

- 📊 **Live Dashboard** — Radiostatus, aktive TG, aktueller Sprecher
- 📱 Als PWA App installierbar — Fullscreen wie auf den Screenshots
- 📋 **Aktivitätslog** — SVXReflector-Aktivität in Echtzeit
- 🔁 **Talk Groups** — TGs direkt anzeigen und aktivieren
- ⌨️ **DTMF-Tastatur** — DTMF-Töne mit einem Tipp senden
- ⚡ **Schnellbefehle** — Konfigurierbare Shortcut-Buttons (aus `config.php`)
- 🌙☀️ **Automatischer Dark/Light Mode** — Folgt der Systemdarstellung des Handys

-----

## 📲 Installation

> Setzt eine funktionierende [SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2) Installation unter `/var/www/html` voraus.

Vom Home-Verzeichnis auf dem Svx Servers / Pi ausführen:

```bash
cd /var/www/html && sudo git clone --depth 1 https://github.com/sebastianmadl/svxlink-mobiledash.git temp && sudo mv temp/mobile mobile && sudo rm -rf temp && sudo chown -R www-data:www-data mobile
```

Danach im Browser aufrufen:

```
http://<IP-des-SvxHosts>/mobile/
```

-----

## 📁 Verzeichnisstruktur

```
/var/www/html/
└── mobile/
    ├── index.php          # Haupt-SPA (Dashboard, Aktivität, TG, DTMF)
    ├── dtmf.php           # Eigenständige DTMF-Seite
    ├── api/
    │   ├── dtmf.php       # DTMF-API-Endpunkt
    │   └── stream.php     # SSE Live-Datenstrom
    ├── css/
    │   └── app.css        # Styles inkl. Dark/Light Mode
    └── js/
        └── app.js         # Frontend-Logik
```

-----

## ⚙️ Voraussetzungen

- SVXLink-Dash-V2 installiert und funktionsfähig unter `/var/www/html`
- PHP 7.4+


-----

## 🖌️ Dark / Light Mode

Das Dashboard passt sich automatisch an die Systemdarstellung des Handys an — kein manueller Schalter nötig. Einfach in den Display-Einstellungen des Smartphones zwischen Dark und Light Mode wechseln.

<p align="center">
  <img src="/images/dark.png" width="350">
  <img src="/images/light.png" width="350">
</p>

-----

## 📱 Als App installieren (PWA)

Das Dashboard kann auf dem Homebildschirm installiert werden und verhält 
sich dann wie eine native App — ohne Browser-Leiste, im Vollbild.

**iOS (Safari):**
1. Seite in Safari öffnen
2. Teilen-Symbol antippen (⬆️)
3. „Zum Home-Bildschirm" wählen
4. Namen bestätigen → „Hinzufügen"

**Android (Chrome):**
1. Seite in Chrome öffnen
2. Menü antippen (⋮)
3. „Zum Startbildschirm hinzufügen" wählen
4. Namen bestätigen → „Hinzufügen"

> Tipp: Unter iOS muss zwingend Safari verwendet werden, 
> unter Android funktioniert es auch mit Firefox oder Edge.

 -----

## 👤 Autor

**OE1SXM — Sebastian M.**  
[github.com/sebastianmadl](https://github.com/sebastianmadl)

-----

## 🙏 Danksagung

- Ursprüngliches Dashboard: [DL3EL/SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2)
- SVXLink Projekt: [sm0svx/svxlink](https://github.com/sm0svx/svxlink)

-----

## 📄 Lizenz

Dieses Projekt baut auf SVXLink-Dash-V2 auf. Lizenzdetails bitte beim Originalprojekt nachlesen.
