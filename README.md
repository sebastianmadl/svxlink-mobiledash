# 📡 SVXLink Mobile Dashboard Ver 2.0 <img src="mobile/images/icon-512.png" width="40">

🇩🇪 [Deutsche Version dieser README](README_de.md)

A clean, smartphone-optimized mobile frontend for SVXLink reflector nodes. Originally built as an extension for [SVXLink-Dash-V2 by DL3EL](https://github.com/DL3EL/SVXLink-Dash-V2) — now also fully compatible with the original dashboard by [SP2ONG and SP0DZ](https://github.com/SP2ONG/SVXLink-Dashboard).

<p align="center">
  <img src="/images/dashboard.png" width="350">
  <img src="/images/activity.png" width="350">
  <img src="/images/talkgroups.png" width="350">
  <img src="/images/dtmf.png" width="350">
</p>

-----

## ✨ Features

### 🆕 New in Version 2.0
- 🏷️ **TG Renaming** — Rename talk groups with custom names, DL3EL database or a mix of both
- 🛡️ **Safe Updates** — `config.php` and `mobile_settings.json` excluded from Git, auto-created from templates on first start
- 🔁 **SSE Reconnect** — Automatic reconnect with exponential backoff (max. 30s) on connection loss
- 🚀 **deploy.sh** — Installer/Updater script with pre-flight check and automatic config backup & restore


### 🖥️ Dashboard & Live Data
- 📊 **Live Dashboard** — Radio status, active TGs and current speaker via SSE
- 📋 **Activity Log** — SVXReflector activity updated in real time
- 🔁 **Talk Groups** — View, tap to activate or enter a TG number manually
- ⌨️ **DTMF Keypad** — Send DTMF tones with a single tap
- ⚡ **Quick Commands** — Configurable shortcut buttons (read from `include/config.php`)

### 🎨 Appearance
- 🌙☀️⚙️ **Dark / Light / System Theme** — Choose manually or follow system appearance
- 🌐 **Bilingual UI** — English 🇬🇧 and German 🇩🇪 — switchable at any time without page reload

### ⚙️ Configuration & Setup
- 🚀 **First-Start Wizard** — Language and theme selection on first launch, no config editing needed
- ⚙️ **Settings Menu** — Tap the connection badge (top right corner) to change language & theme on the fly
- 🔧 **Portable Install** — No hardcoded paths, works alongside any existing dashboard installation
- ⌨️ **DTMF** — Sends tones directly to `/tmp/dtmf_svx` — compatible with SP2ONG/SP0DZ and DL3EL dashboards

### 📱 Mobile
- 📱 **Installable as PWA** — Fullscreen app experience on iOS & Android


-----

## 📲 Installation

> **Note:** The commands below assume your web root is `/var/www/html` — the default for both [SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2) and the [SP2ONG/SP0DZ Dashboard](https://github.com/SP2ONG/SVXLink-Dashboard). If your setup uses a different web root, adjust the deploy.sh accordingly. (Line 16 DST= ... )

Clone and install:
```bash
cd /home/svxlink && sudo git clone --depth 1 https://github.com/sebastianmadl/svxlink-mobiledash.git temp && sudo mv temp/mobile mobile && sudo mv temp/deploy.sh deploy.sh && sudo rm -rf temp && sudo bash deploy.sh
```

Then open in your browser:
```
http://<IP-of-SvxHost>/mobile/
```

On first launch the setup wizard will guide you through language and theme selection.

-----

## 🔄 Update from Ver 1.x

> **Note:** Navigate to the web root of your dashboard installation before running the update — in most cases `/var/www/html`.
```bash
cd /var/www/html
```

Remove the old installation:
```bash
sudo rm -rf mobile
```

Then follow the [Installation](#-installation) steps above.

> **Note:** This will reset your language and theme selection. The setup wizard will run again on first launch.

-----

## 📱 Install as App (PWA)

The dashboard can be installed on your home screen and runs like a native app — no browser bar, full screen.

<img src="mobile/images/icon-512.png" width="100">

**iOS (Safari):**
1. Open the page in Safari
2. Tap the share icon (⬆️)
3. Select "Add to Home Screen"
4. Confirm → "Add"

**Android (Chrome):**
1. Open the page in Chrome
2. Tap the menu (⋮)
3. Select "Add to Home Screen"
4. Confirm → "Add"

> On iOS, Safari must be used. On Android, Firefox or Edge also work.

-----

## 🖌️ Dark / Light Mode

Choose between Dark, Light or System theme during the first-start wizard or at any time via the settings menu — tap the connection badge in the top right corner.

<p align="center">
  <img src="/images/dark.png" width="350">
  <img src="/images/light.png" width="350">
</p>

-----

## 📁 Directory Structure
```
/var/www/html/
├── include/
│   └── config.php                # Read by configs/config.php → SVX paths (SVXCONFPATH, SVXLOGPATH etc.)
├── images/
│   ├── favicon.ico               # Used by index.php, setup.php
│   └── svxlink.ico               # Used by index.php (header logo)
├── index.php                     # Linked from mobile index.php (↗ Full Dashboard)
│
└── mobile/
    ├── index.php                         # Main SPA (EN + DE, single file)
    ├── setup.php                         # Welcome Page (first start only)
    ├── settings.php                      # AJAX handler – saves language, theme & TG settings
    ├── manifest.json                     # PWA manifest
    ├── configs/
    │   ├── config.php                    # Central config ← NOT in Git, auto-copied from .example
    │   ├── config.php.example            # Template for config.php ← in Git
    │   ├── mobile_settings.json          # User settings (language, theme) ← NOT in Git, auto-copied from .example
    │   └── mobile_settings.json.example  # Template for mobile_settings.json ← in Git
    ├── css/
    │   └── app.css                       # Shared styles incl. Dark/Light/System theme
    ├── js/
    │   └── app.js                        # Frontend logic – live language & theme switch
    ├── api/
    │   ├── dtmf.php                      # DTMF endpoint – writes code to /tmp/dtmf_svx
    │   └── stream.php                    # SSE stream – tails SVXLink log
    └── images/
        ├── icon-192.png                  # PWA icon (small)
        └── icon-512.png                  # PWA icon (large)

```

-----

## 📋 Changelog

### Ver 2.0 — 2026-03-10
- **TG Renaming** — talk groups can be renamed (Custom, DL3EL, Mixed)
- **TG Source** — TG name source selectable: `off` / `custom` / `dl3el` / `mixed`
- **TG Editor** — add/edit custom TG entries directly in the settings menu
- **SSE Reconnect** — exponential backoff (max. 30s) on connection loss
- **`configs/` restructured** — `config.php` & `config.php.example` as templates,
  auto-copied on first start
- **`mobile_settings.json.example`** — new template, auto-copied to `mobile_settings.json`
  on first start
- **`deploy.sh`** — new deploy/update script with Installer/Updater detection,
  pre-flight check, config backup & restore
- **`api/stream.php`** — reads SVX paths via `configs/config.php` for non DL3EL Dashboards

### Ver 1.1 — 2026-02-20
- **New structure** — `en/` subdirectory removed, single `index.php` handles both languages
- **`configs/` folder** — `config.php` and `mobile_config.php` moved into dedicated subfolder
- **`mobile_settings.json`** — language & theme now stored as JSON instead of PHP defines
- **`setup.php`** — new welcome page on first launch with language & theme selection incl. live preview
- **`settings.php`** — AJAX handler that writes to `mobile_settings.json`
- **Live switching** — language & theme change without page reload via `data-i18n` and `CFG.allStrings`
- **Settings menu** — tap the connection badge (top right corner) to open the settings modal
- **`api/dtmf.php`** — simplified, writes directly to `/tmp/dtmf_svx`, compatible with SP2ONG/SP0DZ and DL3EL

-----

## 🙏 Credits

- [DL3EL/SVXLink-Dash-V2](https://github.com/DL3EL/SVXLink-Dash-V2)
- [SP2ONG/SVXLink-Dashboard](https://github.com/SP2ONG/SVXLink-Dashboard)
- [sm0svx/svxlink](https://github.com/sm0svx/svxlink)

-----

## 👤 Author

**OE1SXM — Sebastian M.**  
[github.com/sebastianmadl](https://github.com/sebastianmadl)

-----

## 📄 License

This project builds on SVXLink-Dash-V2. Please refer to the original project for license details.
