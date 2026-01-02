(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // 1. Initialer Status-Check für alle Zeilen im Dashboard
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // 2. Details-Bereich auf/zu klappen
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

        // 4. Bulk-Update Checkbox-Logik (Alle auswählen)
        $(document).on('change', '.select-all-updates', function() {
            const id = $(this).data('id');
            const isChecked = $(this).is(':checked');
            $(`#update-container-${id} .update-cb`).prop('checked', isChecked).trigger('change');
        });

        // Bulk-Button aktivieren/deaktivieren
        $(document).on('change', '.update-cb', function() {
            const id = $(this).data('id');
            const anyChecked = $(`#update-container-${id} .update-cb:checked`).length > 0;
            $(`#update-container-${id} .btn-run-bulk-update`).prop('disabled', !anyChecked);
        });

        // 5. Sequenzielles Bulk-Update mit 2s Pause (Cooldown)
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
                    row.css('background', '#fbeaea').find('.update-status-label').text('❌ Timeout');
                }
                
                row.css('opacity', '1');

                // Progress Bar aktualisieren
                const percent = Math.round(((i + 1) / total) * 100);
                progressBar.css('width', percent + '%').text(percent + '%');

                // COOLDOWN: 2 Sekunden warten, bevor das nächste Element angefragt wird (außer beim letzten)
                if (i < total - 1) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }

            btn.text('Abgeschlossen');
            setTimeout(() => {
                loadSiteStatus(id);
                progressBarContainer.fadeOut();
            }, 3000);
        });

        // 6. Modal Logik (Edit/Delete)
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
            if (confirm('Website wirklich löschen?')) {
                $.post(wpmmData.ajax_url, { action: 'wpmm_delete_site', nonce: wpmmData.nonce, id: id }, function() {
                    location.reload();
                });
            }
        });

        // 7. Neue Seite hinzufügen via AJAX & Key Anzeige
        $('#add-site-ajax-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#submit-site');
            btn.prop('disabled', true).text('Speichere...');

            $.post(wpmmData.ajax_url, {
                action: 'wpmm_add_site',
                nonce: wpmmData.nonce,
                name: $('#site-name').val(),
                url: $('#site-url').val()
            }, function(r) {
                if(r.success) {
                    $('#add-site-ajax-form').slideUp();
                    $('#generated-api-key').val(r.data.api_key); // Key in das neue Feld schreiben
                    $('#setup-success').fadeIn();
                } else {
                    alert('Fehler beim Speichern.');
                    btn.prop('disabled', false).text('Seite registrieren');
                }
            });
        });

        // 8. API Key Kopier-Funktion
        $(document).on('click', '.btn-copy-key', function() {
            const keyField = $('#generated-api-key');
            keyField.select();
            document.execCommand('copy');
            
            const btn = $(this);
            const originalText = btn.text();
            btn.text('Kopiert!').css('background', '#46b450').css('color', '#fff');
            setTimeout(() => { btn.text(originalText).css('background', '').css('color', ''); }, 2000);
        });
    });

    // API Status laden
    function loadSiteStatus(id) {
        $(`#status-${id}`).html('<span style="color:#999;">...</span>');
        $.post(wpmmData.ajax_url, { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id }, function(r) {
            if(r.success && r.data && r.data.updates) {
                siteDataCache[id] = r.data;
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version || '-');
                let badges = `<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>`;
                badges += `<span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`;
                if(c.core > 0) badges += `<span class="cluster-badge has-updates" style="background:#d64e07;color:#fff;">CORE</span>`;
                $(`#status-${id}`).html(badges);
            } else {
                $(`#status-${id}`).html('<span style="color:red;font-size:10px;">Fehler</span>');
            }
        });
    }

    // Details Grid rendern
    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $(`#update-container-${id}`);
        if(!data) return;

        let html = `
            <div class="bulk-controls">
                <label style="cursor:pointer;"><input type="checkbox" class="select-all-updates" data-id="${id}"> <strong>Alle auswählen</strong></label>
                <div id="progress-container-${id}" class="wpmm-progress-container" style="display:none; flex-grow:1; margin: 0 20px;">
                    <div id="progress-bar-${id}" class="wpmm-progress-bar">0%</div>
                </div>
                <button class="button button-primary btn-run-bulk-update" data-id="${id}" disabled>Ausgewählte aktualisieren</button>
            </div>
            <div class="wpmm-details-grid">`;

        if(data.updates.counts.core > 0) {
            html += `
                <div class="update-section core-box">
                    <label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="core" data-slug="core" data-id="${id}"> <strong>WordPress Core Update</strong></label>
                    <div class="update-status-label">Update verfügbar</div>
                </div>`;
        }

        html += '<div class="update-section"><h4>Plugins</h4>';
        data.updates.plugin_names.forEach(slug => {
            html += `<div class="update-row"><label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="plugin" data-slug="${slug}" data-id="${id}"> ${slug.split('/')[0]}</label><div class="update-status-label"></div></div>`;
        });
        html += '</div><div class="update-section"><h4>Themes</h4>';
        data.updates.theme_names.forEach(slug => {
            html += `<div class="update-row"><label style="cursor:pointer;"><input type="checkbox" class="update-cb" data-type="theme" data-slug="${slug}" data-id="${id}"> ${slug}</label><div class="update-status-label"></div></div>`;
        });
        html += '</div></div>';
        container.html(html);
    }
})(jQuery);
