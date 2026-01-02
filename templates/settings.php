<?php
/**
 * Settings Template
 * Einstellungen und neue Seiten hinzufügen
 */

// Display Mode des aktuellen Users laden
$display_mode = get_user_meta(get_current_user_id(), 'wpmm_display_mode', true) ?: 'grid';
?>

<div class="wrap wpmm-container">
    <h1>Einstellungen</h1>
    
    <div style="max-width: 900px;">
        
        <!-- Ansichts-Optionen -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2>📊 Ansichts-Optionen</h2>
            </div>
            <div class="inside" style="padding: 20px;">
                <p>Wähle aus, wie deine WordPress-Seiten im Dashboard dargestellt werden sollen:</p>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button id="set-grid-mode" class="button <?= $display_mode === 'grid' ? 'button-primary' : '' ?>">
                        📱 Card Ansicht (Grid)
                    </button>
                    <button id="set-list-mode" class="button <?= $display_mode === 'list' ? 'button-primary' : '' ?>">
                        📋 Listen Ansicht (Table)
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Neue Seite hinzufügen -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2>➕ Neue WordPress-Seite hinzufügen</h2>
            </div>
            <div class="inside" style="padding: 20px;">
                <form id="add-site-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="site-name">Name der Seite</label>
                            </th>
                            <td>
                                <input type="text" id="site-name" name="name" class="regular-text" required 
                                       placeholder="Meine WordPress Seite">
                                <p class="description">Ein beschreibender Name für diese Installation</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="site-url">URL der Seite</label>
                            </th>
                            <td>
                                <input type="url" id="site-url" name="url" class="regular-text" required 
                                       placeholder="https://example.com">
                                <p class="description">Die vollständige URL zur WordPress-Installation (mit https://)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            Seite anlegen & API-Key generieren
                        </button>
                    </p>
                </form>
                
                <div id="new-site-key" style="display: none; background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-top: 20px;">
                    <h3 style="margin-top: 0; color: #155724;">✅ Seite erfolgreich angelegt!</h3>
                    <p><strong>API-Key (nur einmal sichtbar):</strong></p>
                    <div style="background: white; padding: 15px; border: 2px dashed #28a745; border-radius: 5px; font-family: monospace; font-size: 16px; word-break: break-all;">
                        <span id="generated-api-key"></span>
                    </div>
                    <button id="copy-key-btn" class="button" style="margin-top: 10px;">
                        📋 API-Key kopieren
                    </button>
                    <p style="margin-top: 15px; color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; border: 1px solid #ffeaa7;">
                        <strong>Wichtig:</strong> Notiere dir diesen Key jetzt! Er wird nicht wieder angezeigt.<br>
                        Installiere das Bridge-Plugin (<code>wordpress-bridge-connector.php</code>) auf der Zielseite und trage diesen Key dort ein.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Anleitung -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2>📖 So funktioniert's</h2>
            </div>
            <div class="inside" style="padding: 20px;">
                <ol style="line-height: 2;">
                    <li><strong>Neue Seite anlegen:</strong> Füge oben eine WordPress-Installation hinzu und erhalte einen API-Key</li>
                    <li><strong>Bridge-Plugin installieren:</strong> Installiere das Plugin <code>wordpress-bridge-connector.php</code> auf der Ziel-WordPress-Seite</li>
                    <li><strong>API-Key eintragen:</strong> Gehe auf der Zielseite zu <em>Einstellungen → WP Bridge</em> und trage den Key ein</li>
                    <li><strong>Dashboard nutzen:</strong> Ab jetzt kannst du von hier aus Updates verwalten!</li>
                </ol>
                
                <div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; margin-top: 20px;">
                    <h4 style="margin-top: 0;">💡 Tipp: Bridge-Plugin</h4>
                    <p>Das Bridge-Plugin sollte sich bereits in deinem ursprünglichen Plugin-Verzeichnis befinden als <code>wordpress-bridge-connector.php</code>. 
                    Du kannst es manuell auf die Zielseiten hochladen und dort den generierten API-Key in den Plugin-Einstellungen eintragen.</p>
                </div>
            </div>
        </div>
        
        <!-- Sicherheitshinweise -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2>🔒 Sicherheitshinweise</h2>
            </div>
            <div class="inside" style="padding: 20px;">
                <ul style="line-height: 2;">
                    <li>Alle verwalteten Seiten sollten über <strong>HTTPS</strong> erreichbar sein</li>
                    <li>API-Keys niemals öffentlich teilen oder in Versionskontrolle einchecken</li>
                    <li>Regelmäßig prüfen, welche Seiten Zugriff haben</li>
                    <li>Bei Verdacht auf Kompromittierung: Seite löschen und mit neuem Key neu anlegen</li>
                </ul>
            </div>
        </div>
        
        <!-- Über das Plugin -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2>ℹ️ Über dieses Plugin</h2>
            </div>
            <div class="inside" style="padding: 20px;">
                <p><strong>WP Maintenance Monitor</strong> Version 3.0.0</p>
                <p>Ein zentrales Dashboard zur Verwaltung mehrerer WordPress-Installationen mit Remote-Update-Funktionen.</p>
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <strong>Features:</strong>
                </p>
                <ul>
                    <li>✅ Zentrale Verwaltung mehrerer WordPress-Instanzen</li>
                    <li>✅ Remote-Updates für Plugins, Themes und WordPress Core</li>
                    <li>✅ Live-Status-Überwachung</li>
                    <li>✅ Aktivitätenprotokoll</li>
                    <li>✅ Sichere API-Key-basierte Authentifizierung</li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<style>
    .wpmm-container .postbox {
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .wpmm-container .postbox-header {
        border-bottom: 1px solid #c3c4c7;
    }
    .wpmm-container .postbox-header h2 {
        padding: 12px;
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
    }
</style>

<script>
jQuery(document).ready(function($) {
    var currentSiteId = null;
    var currentApiKey = null;
    
    // Display Mode ändern
    $('#set-grid-mode, #set-list-mode').on('click', function() {
        var mode = $(this).attr('id') === 'set-grid-mode' ? 'grid' : 'list';
        
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_set_display_mode',
            nonce: wpmmData.nonce,
            mode: mode
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
    
    // Seite hinzufügen
    $('#add-site-form').on('submit', function(e) {
        e.preventDefault();
        
        var name = $('#site-name').val();
        var url = $('#site-url').val();
        
        // URL validieren und bereinigen
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            alert('Bitte gib eine vollständige URL mit http:// oder https:// an');
            return;
        }
        
        // Trailing Slash entfernen
        url = url.replace(/\/$/, '');
        
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_add_site',
            nonce: wpmmData.nonce,
            name: name,
            url: url
        }, function(response) {
            if (response.success) {
                currentSiteId = response.data.site_id;
                currentApiKey = response.data.api_key;
                
                $('#generated-api-key').text(currentApiKey);
                $('#new-site-key').slideDown();
                $('#add-site-form')[0].reset();
                
                // Scroll zum Key
                $('html, body').animate({
                    scrollTop: $('#new-site-key').offset().top - 100
                }, 500);
            } else {
                alert('Fehler beim Anlegen der Seite: ' + (response.data || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Netzwerkfehler beim Anlegen der Seite');
        });
    });
    
    // API-Key kopieren
    $('#copy-key-btn').on('click', function() {
        var key = $('#generated-api-key').text();
        
        // Moderne Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(key).then(function() {
                var btn = $('#copy-key-btn');
                btn.text('✅ Kopiert!');
                setTimeout(function() {
                    btn.text('📋 API-Key kopieren');
                }, 2000);
            }).catch(function() {
                fallbackCopyToClipboard(key);
            });
        } else {
            // Fallback für ältere Browser
            fallbackCopyToClipboard(key);
        }
    });
    
    // Fallback Kopierfunktion
    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                var btn = $('#copy-key-btn');
                btn.text('✅ Kopiert!');
                setTimeout(function() {
                    btn.text('📋 API-Key kopieren');
                }, 2000);
            } else {
                alert('Kopieren fehlgeschlagen. Bitte manuell kopieren.');
            }
        } catch (err) {
            alert('Kopieren fehlgeschlagen. Bitte manuell kopieren.');
        }
        
        document.body.removeChild(textArea);
    }
});
</script>
