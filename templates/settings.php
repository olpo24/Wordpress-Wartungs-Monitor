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
    
    <p><strong>Dein persönlicher API-Key für diese Seite:</strong></p>
    <div style="display:flex; gap:10px; margin-bottom:15px;">
        <input type="text" id="generated-api-key" class="regular-text" readonly style="background:#f0f0f1; font-family:monospace; font-weight:bold;">
        <button type="button" class="button btn-copy-key">Key kopieren</button>
    </div>

    <hr>
    
    <p><strong>Nächste Schritte:</strong></p>
    <ol>
        <li>Lade das Bridge-Plugin herunter: <a href="HIER_DEIN_STATISCHER_LINK" class="button button-small">Download Bridge-Plugin</a></li>
        <li>Installiere es auf der Zielseite.</li>
        <li>Gehe dort zu <strong>Einstellungen -> Bridge Connector</strong> und füge den oben kopierten Key ein.</li>
    </ol>
</div>
        </div>
    </div>
</div>
