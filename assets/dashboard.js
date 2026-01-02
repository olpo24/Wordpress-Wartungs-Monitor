(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // 1. Initialer Status-Check für alle registrierten Seiten
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // 2. Details-Bereich aufklappen/zuklappen
        $(document).on('click', '.btn-toggle-details', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const row = $(`#details-row-${id}`);
            
            // UI Toggle
            row.toggle();
            
            // Falls sichtbar, Inhalte rendern (aus Cache)
            if(row.is(':visible')) {
                renderUpdateLists(id);
            }
        });

        // 3. SSO Login (One-Click)
        $(document).on('click', '.btn-sso-login', function(e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            
            btn.text('⏳...');
            
            $.post(wpmmData.ajax_url, { 
                action: 'wpmm_get_login_url', 
                nonce: wpmmData.nonce, 
                id: id 
            }, function(r) {
                btn.text('Login');
                if(r.success && r.data.login_url) {
                    window.open(r.data.login_url, '_blank');
                } else {
                    alert('Login fehlgeschlagen. Bridge erreichbar?');
                }
            });
        });

        // 4. Update-Befehl ausführen (Core, Plugin oder Theme)
        $(document).on('click', '.btn-run-update', function() {
            const btn = $(this);
            const id = btn.data('id');
            const type = btn.data('type');
            const slug = btn.data('slug');

            btn.prop('disabled', true).text('⏳ läuft...');

            $.post(wpmmData.ajax_url, {
                action: 'wpmm_execute_update',
                nonce: wpmmData.nonce,
                id: id,
                update_type: type,
                slug: slug
            }, function(r) {
                // Wir prüfen auf r.data.success, da die Bridge dieses Resultat liefert
                if(r.success && r.data && r.data.success) {
                    btn.text('✅ Fertig').css('background', '#46b450').css('color', '#fff');
                    // Status nach 3 Sek neu laden, um Anzeige zu aktualisieren
                    setTimeout(() => loadSiteStatus(id), 3000);
                } else {
                    const error = (r.data && r.data.error) ? r.data.error : 'Fehler';
                    btn.text('❌ ' + error).prop('disabled', false);
                    console.error("Update-Fehler:", r);
                }
            }).fail(function() {
                btn.text('❌ Timeout/Server-Fehler').prop('disabled', false);
            });
        });

        // 5. Seite Bearbeiten (Modal öffnen)
        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        // 6. Modal schließen
        $('.close-edit-modal').on('click', function() { 
            $('#edit-modal').hide(); 
        });

        // 7. Website-Daten aktualisieren (Speichern im Modal)
        $('#edit-site-form').on('submit', function(e) {
            e.preventDefault();
            const id = $('#edit-site-id').val();
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_update_site',
                nonce: wpmmData.nonce,
                id: id,
                name: $('#edit-site-name').val(),
                url: $('#edit-site-url').val()
            }, function(r) {
                if(r.success) {
                    location.reload();
                } else {
                    alert('Fehler beim Speichern.');
                }
            });
        });

        // 8. Website LÖSCHEN (Innerhalb des Modals)
        $(document).on('click', '.btn-delete-site', function(e) {
            e.preventDefault();
            const id = $('#edit-site-id').val();
            
            if (!id) return;

            if (confirm('Möchtest du diese Website wirklich aus dem Monitor löschen?')) {
                const btn = $(this);
                btn.prop('disabled', true).text('Lösche...');

                $.post(wpmmData.ajax_url, { 
                    action: 'wpmm_delete_site', 
                    nonce: wpmmData.nonce, 
                    id: id 
                }, function(r) {
                    if (r.success) {
                        $('#edit-modal').hide();
                        location.reload();
                    } else {
                        alert('Löschen fehlgeschlagen.');
                        btn.prop('disabled', false).text('Löschen');
                    }
                });
            }
        });
    });

    // Hilfsfunktion: Status via API abfragen
    function loadSiteStatus(id) {
        $(`#status-${id}`).html('<span style="color:#999;">Prüfe...</span>');
        
        $.post(wpmmData.ajax_url, { 
            action: 'wpmm_get_status', 
            nonce: wpmmData.nonce, 
            id: id 
        }, function(r) {
            if(r.success && r.data && r.data.updates) {
                // Daten für Details-Ansicht zwischenspeichern
                siteDataCache[id] = r.data;
                
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version || '-');
                
                // Badges zusammenbauen
                let badgeHtml = `
                    <span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>
                    <span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>
                `;
                
                // Core-Update besonders hervorheben
                if(c.core > 0) {
                    badgeHtml += `<span class="cluster-badge has-updates" style="background:#d64e07; color:white; border-color:#d64e07;">CORE</span>`;
                }
                
                $(`#status-${id}`).html(badgeHtml);

                // Falls die Details gerade offen sind, sofort neu rendern
                if($(`#details-row-${id}`).is(':visible')) {
                    renderUpdateLists(id);
                }

            } else {
                const errorInfo = (r.data && r.data.error) ? r.data.error : 'API-Fehler';
                $(`#status-${id}`).html(`<span style="color:red; font-size:10px;">${errorInfo}</span>`);
            }
        });
    }

    // Hilfsfunktion: Update-Liste im aufgeklappten Bereich rendern
    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $(`#update-container-${id}`);
        
        if(!data) {
            container.html('<p>Lade Daten...</p>');
            return;
        }

        let html = '<div class="wpmm-details-grid">';

        // Sektion: WordPress Core
        if(data.updates.counts.core > 0) {
            html += `
                <div class="update-section core-box">
                    <div>
                        <h4>WordPress Core</h4>
                        <p>Version ${data.version} -> Update verfügbar!</p>
                    </div>
                    <button class="button button-primary btn-run-update" data-id="${id}" data-type="core">Jetzt aktualisieren</button>
                </div>`;
        }

        // Sektion: Plugins
        html += '<div class="update-section"><h4>Verfügbare Plugin-Updates</h4>';
        if(data.updates.plugin_names && data.updates.plugin_names.length > 0) {
            data.updates.plugin_names.forEach(slug => {
                const displayName = slug.split('/')[0].replace(/-/g, ' ');
                html += `
                    <div class="update-row">
                        <span>${displayName}</span>
                        <button class="button button-small btn-run-update" data-id="${id}" data-type="plugin" data-slug="${slug}">Update</button>
                    </div>`;
            });
        } else {
            html += '<p style="color:green;">✔ Alle Plugins sind aktuell.</p>';
        }
        html += '</div>';

        // Sektion: Themes
        html += '<div class="update-section"><h4>Verfügbare Theme-Updates</h4>';
        if(data.updates.theme_names && data.updates.theme_names.length > 0) {
            data.updates.theme_names.forEach(slug => {
                html += `
                    <div class="update-row">
                        <span>${slug}</span>
                        <button class="button button-small btn-run-update" data-id="${id}" data-type="theme" data-slug="${slug}">Update</button>
                    </div>`;
            });
        } else {
            html += '<p style="color:green;">✔ Alle Themes sind aktuell.</p>';
        }
        html += '</div>';

        html += '</div>'; // Ende Grid
        container.html(html);
    }

})(jQuery);
