# Olpo Wordpress Wartungs Monitor 🛠️

Ein zentrales Dashboard zur Verwaltung mehrerer WordPress-Instanzen inklusive Remote-Updates und One-Click SSO Login.

## Projektstruktur
Das Repository ist in zwei Hauptkomponenten unterteilt:

* **/owwm:** Das zentrale Kontrollzentrum. Installiere dies auf deiner Hauptseite.
* **/owwm-child:** Der Connector für die Zielseiten. Installiere dies auf jeder Seite, die du überwachen möchtest.

## Features
- ✅ **Zentrales Dashboard:** Alle WordPress-Seiten auf einen Blick.
- ✅ **Bulk Updates:** Plugins, Themes und Core-Updates per Checkbox auswählen und sequentiell (mit Cooldown) abarbeiten.
- ✅ **Progress Bar:** Echtzeit-Fortschrittsanzeige während der Update-Vorgänge.
- ✅ **One-Click Login (SSO):** Direktes Einloggen in das Backend der Zielseiten ohne Passworteingabe.
- ✅ **Sichere API:** Kommunikation über individuelle API-Keys pro Seite.

## Installation

### 1. Dashboard einrichten
1. Lade das `owwm.zip` aus den [Releases](../../releases/latest) herunter.
2. Installiere und aktiviere es auf deiner Haupt-WordPress-Instanz.
3. Gehe zu **Maintenance -> Einstellungen** und füge eine neue Website hinzu.
4. Kopiere den generierten **API-Key**.

### 2. Bridge (Zielseite) einrichten
1. Lade das `owwm-child.zip` herunter.
2. Installiere und aktiviere es auf der Zielseite.
3. Navigiere zu **Einstellungen -> Bridge Connector**.
4. Füge den API-Key ein und speichere.

## Entwicklung & Automatisierung
Dieses Repository nutzt **GitHub Actions**, um bei jedem neuen Release-Tag (z.B. `v1.0.0`) automatisch fertige Plugin-Zips zu erstellen.



## Sicherheitshinweise
- Die API-Kommunikation erfolgt über den Header `X-Bridge-Key`.
- Es wird empfohlen, die Zielseiten über HTTPS zu betreiben.
- Der SSO-Login-Token ist nur 60 Sekunden gültig und wird nach Gebrauch sofort gelöscht.
