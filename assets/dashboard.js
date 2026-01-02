(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // 1. Initialer Status-Check für alle Zeilen
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // 2. Details-Bereich auf/zu
        $(document).on('click', '.btn-toggle-details', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const row = $(`#details-row-${id}`);
            row.toggle();
            if(row.is(':visible')) renderUpdateLists(id);
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
                }
            });
        });

        // 4. Bulk-Update Checkbox-Steuerung
        $(document).on('change', '.select-all-updates', function() {
            const id = $(this).data('id');
            const isChecked = $(this).is(':checked');
            $(`#update-container-${id} .update-cb`).prop('checked', isChecked).trigger('change');
        });

        $(document).on('change', '.update-cb', function() {
            const id = $(this).data('id');
            const anyChecked = $(`#update-container-${id} .update-cb:checked`).length > 0;
            $(`#update-container-${id} .btn-run-bulk-update`).prop('disabled', !anyChecked);
        });

        // 5. Sequenzielles Bulk-Update mit Progress Bar
        $(document).on('click', '.btn-run-bulk-update', async function() {
            const btn = $(this);
            const id = btn.data('id');
            const selected = $(`#update-container-${id} .update-cb:checked`);
            const total = selected.length;
            
            if (!confirm(`Möchtest du diese ${total} Updates jetzt nacheinander ausführen?`)) return;

            // UI Vorbereitung
            btn.prop('disabled', true).text('⌛ Updates laufen...');
            const progressBarContainer = $(`#progress-container-${id}`);
            const progressBar = $(`#progress-bar-${id}`);
            progressBarContainer.show();
            progressBar.css('width', '0%').text('0%');

            // Queue abarbeiten
       // Die Bulk-Update Schleife (Sequentiell mit Cooldown)
        for (let i = 0; i < total; i++) {
            const cb = $(selected[i]);
            const type = cb.data('type');
            const slug = cb.data('slug');
            const row = cb.closest('.update-row, .core-box');

            row.css('background', '#fff9e0').css('opacity', '0.7');
            
            try {
                const response = await $.post(wpmmData.ajax_url, {
                    action: 'wpmm_execute_update',
                    nonce: wpmmData.nonce,
                    id: id,
                    update_type: type,
                    slug: slug
                });

                if (response.success && response.data && response.data.success) {
                    row.css('background', '#e7f4e9').find('input').remove();
                    row.find('.update-status-label').html('<span style="color:green; font-weight:bold;">✔ OK</span>');
                } else {
                    row.css('background', '#fbeaea').find('.update-status-label').html('<span style="color:red;">❌ Fehler</span>');
                }
            } catch (e) {
                row.css('background', '#fbeaea');
            }
            
            row.css('opacity', '1');

            // --- NEU: Cooldown Pause von 2 Sekunden ---
            if (i < total - 1) { // Nur wenn noch ein Element folgt
                await new Promise(resolve => setTimeout(resolve, 2000));
            }

            const percent = Math.round(((i + 1) / total) * 100);
            progressBar.css('width', percent + '%').text(percent + '%');
        }

            btn.text('Abgeschlossen');
            
            // Nach Abschluss Status der Seite im Hintergrund neu laden
            setTimeout(() => {
                loadSiteStatus(id);
                progressBarContainer.fadeOut();
            }, 3000);
        });

        // 6. Modal Logik (Bearbeiten/Löschen)
        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        $('.close-edit-modal').on('click', function() { $('#edit-modal').hide(); });

        $(document).on('click', '.btn-delete-site', function(e) {
            e.preventDefault();
            const id = $('#edit-site-id').val();
            if (confirm('Möchtest du diese Website wirklich unwiderruflich löschen?')) {
                $.post(wpmmData.ajax_url, { 
                    action: 'wpmm_delete_site', 
                    nonce: wpmmData.nonce, 
                    id: id 
                }, function() {
                    location.reload();
                });
            }
        });

        // 7. Formular für neue Seiten (AJAX-basiert)
        $('#add-site-ajax-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#submit-site');
            btn.prop('disabled', true).text('Registriere...');

            $.post(wpmmData.ajax_url, {
                action: 'wpmm_add_site',
                nonce: wpmmData.nonce,
                name: $('#site-name').val(),
                url: $('#site-url').val()
            }, function(r) {
                if(r.success) {
                    $('#add-site-ajax-form').slideUp();
                    const downloadUrl = `admin.php?action=download_bridge&api_key=${r.data.api_key}`;
                    $('#download-bridge-btn').attr('href', downloadUrl);
                    $('#setup-success').fadeIn();
                } else {
                    alert('Fehler: ' + (r.data.message || 'Unbekannter Fehler'));
                    btn.prop('disabled', false).text('Seite registrieren');
                }
            });
        });
    });

    // Hilfsfunktion: API Status laden
    function loadSiteStatus(id) {
        $(`#status-${id}`).html('<span style="color:#999;">...</span>');
        $.post(wpmmData.ajax_url, { 
            action: 'wpmm_get_status', 
            nonce: wpmmData.nonce, 
            id: id 
        }, function(r) {
            if(r.success && r.data && r.data.updates) {
                siteDataCache[id] = r.data;
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version || '-');
                
                let badges = `<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>`;
                badges += `<span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`;
                
                if(c.core > 0) {
                    badges += `<span class="cluster-badge has-updates" style="background:#d64e07;color:#fff;border-color:#d64e07;">CORE</span>`;
                }
                $(`#status-${id}`).html(badges);
            } else {
                $(`#status-${id}`).html('<span style="color:red;font-size:10px;">Offline/API Fehler</span>');
            }
        });
    }

    // Hilfsfunktion: Details im Grid rendern
    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $(`#update-container-${id}`);
        if(!data) {
            container.html('<p>Keine Daten vorhanden. Bitte lade die Seite neu.</p>');
            return;
        }

        let html = `
            <div class="bulk-controls">
                <label style="cursor:pointer;"><input type="checkbox" class="select-all-updates" data-id="${id}"> <strong>Alle auswählen</strong></label>
                <div id="progress-container-${id}" class="wpmm-progress-container" style="display:none; flex-grow:1; margin: 0 20px;">
                    <div id="progress-bar-${id}" class="wpmm-progress-bar">0%</div>
                </div>
                <button class="button button-primary btn-run-bulk-update" data-id="${id}" disabled>Ausgewählte aktualisieren</button>
            </div>
            <div class="wpmm-details-grid">`;

        // WordPress Core
        if(data.updates.counts.core > 0) {
            html += `
                <div class="update-section core-box">
                    <label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="core" data-slug="core" data-id="${id}"> <strong>WordPress Core Update</strong></label>
                    <div class="update-status-label">Update auf die neueste Version verfügbar</div>
                </div>`;
        }

        // Plugins Sektion
        html += '<div class="update-section"><h4>Plugins</h4>';
        if(data.updates.plugin_names.length > 0) {
            data.updates.plugin_names.forEach(slug => {
                const displayName = slug.split('/')[0].replace(/-/g, ' ');
                html += `
                    <div class="update-row">
                        <label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="plugin" data-slug="${slug}" data-id="${id}"> ${displayName}</label>
                        <div class="update-status-label"></div>
                    </div>`;
            });
        } else {
            html += '<p style="color:green;">✔ Plugins sind aktuell.</p>';
        }
        html += '</div>';

        // Themes Sektion
        html += '<div class="update-section"><h4>Themes</h4>';
        if(data.updates.theme_names && data.updates.theme_names.length > 0) {
            data.updates.theme_names.forEach(slug => {
                html += `
                    <div class="update-row">
                        <label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="theme" data-slug="${slug}" data-id="${id}"> ${slug}</label>
                        <div class="update-status-label"></div>
                    </div>`;
            });
        } else {
            html += '<p style="color:green;">✔ Themes sind aktuell.</p>';
        }
        html += '</div>';

        html += '</div>'; // Grid Ende
        container.html(html);
    }

})(jQuery);
