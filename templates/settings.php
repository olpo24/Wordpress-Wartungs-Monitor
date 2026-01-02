<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>WP Maintenance Monitor - Einstellungen</h1>

    <div class="postbox" style="margin-top:20px;">
        <div class="postbox-header"><h2 class="hndle">Neue Website hinzufügen</h2></div>
        <div class="inside">
            <form id="add-site-ajax-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="site-name">Anzeigename</label></th>
                        <td><input type="text" id="site-name" class="regular-text" placeholder="z.B. Kundenprojekt Alpha" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="site-url">Website URL</label></th>
                        <td><input type="url" id="site-url" class="regular-text" placeholder="https://beispiel-seite.de" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" id="submit-site" class="button button-primary">Seite registrieren & Bridge generieren</button>
                    <span class="spinner" id="add-site-spinner" style="float:none;"></span>
                </p>
            </form>

            <div id="setup-success" style="display:none; margin-top:20px; padding:20px; background:#fff; border-left:4px solid #46b450; box-shadow:0 1px 1px rgba(0,0,0,0.1);">
                <h3 style="color:#46b450; margin-top:0;">✔ Seite erfolgreich registriert!</h3>
                <p>Der API-Key wurde erstellt. Lade jetzt das vorkonfigurierte Plugin herunter:</p>
                <div style="margin:20px 0;">
                    <a href="#" id="download-bridge-btn" class="button button-primary button-large">
                        <span class="dashicons dashicons-archive" style="vertical-align:middle; margin-top:4px;"></span> Bridge-Plugin (.zip) herunterladen
                    </a>
                </div>
                <p class="description"><strong>Anleitung:</strong> Installiere die ZIP-Datei auf der Zielseite unter <strong>Plugins -> Installieren -> Hochladen</strong> und aktiviere sie. Danach ist die Seite im Dashboard bereit.</p>
            </div>
        </div>
    </div>
</div>
