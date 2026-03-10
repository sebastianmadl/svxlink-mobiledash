#!/bin/bash

# ============================================================
#  SVXLink Mobile Dashboard — Deploy Script
#  OE1SXM
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SRC="/home/svxlink/mobile"
DST="/var/www/html/mobile"
TMP="/tmp/svxlinkmobile_backup"

echo ""

# ── Detect install vs update ──────────────────────────────────
if [ -d "$DST" ]; then
    echo -e "${CYAN}${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}${BOLD}║    SVXLink Mobile Dashboard — Updater    ║${NC}"
    echo -e "${CYAN}${BOLD}║                  OE1SXM                  ║${NC}"
    echo -e "${CYAN}${BOLD}╚══════════════════════════════════════════╝${NC}"
    MODE="Updater"
else
    echo -e "${CYAN}${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}${BOLD}║   SVXLink Mobile Dashboard — Installer   ║${NC}"
    echo -e "${CYAN}${BOLD}║                  OE1SXM                  ║${NC}"
    echo -e "${CYAN}${BOLD}╚══════════════════════════════════════════╝${NC}"
    MODE="Installer"
fi

echo ""

# ── Pre-flight check ─────────────────────────────────────────
echo -e "${BOLD}[ 1/4 ] Checking source files...${NC}"

ERRORS=0
for FILE in \
    "$SRC/index.php" \
    "$SRC/settings.php" \
    "$SRC/setup.php" \
    "$SRC/configs/config.php.example" \
    "$SRC/configs/mobile_settings.json.example"
do
    if [ ! -f "$FILE" ]; then
        echo -e "        ${RED}✘ $FILE not found!${NC}"
        ERRORS=1
    else
        echo -e "        ${GREEN}✔ $(basename $FILE)${NC}"
    fi
done

if [ $ERRORS -eq 1 ]; then
    echo ""
    echo -e "${RED}${BOLD}Deploy aborted — please check missing files.${NC}"
    echo ""
    exit 1
fi

echo ""

# ── Backup ───────────────────────────────────────────────────
echo -e "${BOLD}[ 2/4 ] Backing up existing configs...${NC}"
mkdir -p "$TMP"

if [ -f "$DST/configs/mobile_settings.json" ]; then
    cp "$DST/configs/mobile_settings.json" "$TMP/mobile_settings.json"
    echo -e "        ${GREEN}✔ mobile_settings.json${NC}"
else
    echo -e "        ${YELLOW}⚬ mobile_settings.json not found — will create from template${NC}"
fi

if [ -f "$DST/configs/config.php" ]; then
    cp "$DST/configs/config.php" "$TMP/config.php"
    echo -e "        ${GREEN}✔ config.php${NC}"
else
    echo -e "        ${YELLOW}⚬ config.php not found — will create from template${NC}"
fi

echo ""

# ── Deploy ───────────────────────────────────────────────────
echo -e "${BOLD}[ 3/4 ] Deploying...${NC}"
rm -rf "$DST"
cp -r "$SRC" "$DST"
echo -e "        ${GREEN}✔ Files deployed to $DST${NC}"
echo ""

# ── Restore ──────────────────────────────────────────────────
echo -e "${BOLD}[ 4/4 ] Restoring configs...${NC}"

if [ -f "$TMP/mobile_settings.json" ]; then
    cp "$TMP/mobile_settings.json" "$DST/configs/mobile_settings.json"
    echo -e "        ${GREEN}✔ mobile_settings.json restored${NC}"
elif [ ! -f "$DST/configs/mobile_settings.json" ]; then
    cp "$DST/configs/mobile_settings.json.example" "$DST/configs/mobile_settings.json"
    echo -e "        ${YELLOW}✔ mobile_settings.json created from template${NC}"
fi

if [ -f "$TMP/config.php" ]; then
    cp "$TMP/config.php" "$DST/configs/config.php"
    echo -e "        ${GREEN}✔ config.php restored${NC}"
elif [ ! -f "$DST/configs/config.php" ]; then
    cp "$DST/configs/config.php.example" "$DST/configs/config.php"
    echo -e "        ${YELLOW}✔ config.php created from template${NC}"
fi

chmod 666 "$DST/configs/mobile_settings.json"
rm -rf "$TMP"

echo ""
if [ "$MODE" = "Updater" ]; then
    echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}${BOLD}║    Updater completed successfully!  ✔    ║${NC}"
    echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════╝${NC}"
else
    echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}${BOLD}║   Installer completed successfully!  ✔   ║${NC}"
    echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════╝${NC}"
fi
echo ""
