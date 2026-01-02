(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // Initialer Status-Check
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // Details-Bereich auf/zu
        $(document).on('click', '.btn-toggle-details', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const row = $(`#details-row-${id}`);
            row.toggle();
            if(row.is(':visible')) renderUpdateLists(id);
        });

        // SSO Login
        $(document).on('click', '.btn-sso-login', function(e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            btn.text('⏳...');
            $.post(wpmmData.ajax_url, { action: 'wpmm_get_login_url', nonce: wpmmData.nonce, id: id }, function(r) {
                btn.text('Login');
                if(r.success && r.data.login_url) {
                    window.open(r.data.login_url, '_blank');
                }
            });
        });

        // Bulk-Update Auswahl-Logik
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

        // Die Bulk-Update Schleife (Sequentiell)
        $(document).on('click', '.btn-run-bulk-update', async function() {
            const btn = $(this);
            const id = btn.data('id');
            const selected = $(`#update-container-${id} .update-cb:checked`);
            
            if (!confirm(`Sollen die ${selected.length} ausgewählten Elemente aktualisiert werden?`)) return;

            btn.prop('disabled', true).text('⌛ Updates werden nacheinander ausgeführt...');

            for (let i = 0; i < selected.length; i++) {
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
                        row.find('.update-status-label').html('<span style="color:green;">✔ Aktualisiert</span>');
                    } else {
                        row.css('background', '#fbeaea').find('.update-status-label').html('<span style="color:red;">❌ Fehler</span>');
                    }
                } catch (e) {
                    row.css('background', '#fbeaea');
                }
                row.css('opacity', '1');
            }

            btn.text('Abgeschlossen');
            setTimeout(() => loadSiteStatus(id), 2000);
        });

        // Modal Logik (Edit/Delete)
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
            if (confirm('Diese Seite wirklich löschen?')) {
                $.post(wpmmData.ajax_url, { action: 'wpmm_delete_site', nonce: wpmmData.nonce, id: id }, function() {
                    location.reload();
                });
            }
        });
    });

    function loadSiteStatus(id) {
        $(`#status-${id}`).text('...');
        $.post(wpmmData.ajax_url, { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id }, function(r) {
            if(r.success && r.data && r.data.updates) {
                siteDataCache[id] = r.data;
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version || '-');
                let badges = `<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>`;
                badges += `<span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`;
                if(c.core > 0) badges += `<span class="cluster-badge has-updates" style="background:#d64e07;color:#fff;">CORE</span>`;
                $(`#status-${id}`).html(badges);
            }
        });
    }

    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $(`#update-container-${id}`);
        if(!data) return;

        let html = `
            <div class="bulk-controls">
                <label><input type="checkbox" class="select-all-updates" data-id="${id}"> <strong>Alle auswählen</strong></label>
                <button class="button button-primary btn-run-bulk-update" data-id="${id}" disabled>Ausgewählte aktualisieren</button>
            </div>
            <div class="wpmm-details-grid">`;

        if(data.updates.counts.core > 0) {
            html += `
                <div class="update-section core-box">
                    <label><input type="checkbox" class="update-cb" data-type="core" data-slug="core" data-id="${id}"> <strong>WordPress Core Update</strong></label>
                    <div class="update-status-label">Verfügbar</div>
                </div>`;
        }

        html += '<div class="update-section"><h4>Plugins</h4>';
        data.updates.plugin_names.forEach(slug => {
            html += `<div class="update-row"><label><input type="checkbox" class="update-cb" data-type="plugin" data-slug="${slug}" data-id="${id}"> ${slug.split('/')[0]}</label><div class="update-status-label"></div></div>`;
        });
        html += '</div><div class="update-section"><h4>Themes</h4>';
        data.updates.theme_names.forEach(slug => {
            html += `<div class="update-row"><label><input type="checkbox" class="update-cb" data-type="theme" data-slug="${slug}" data-id="${id}"> ${slug}</label><div class="update-status-label"></div></div>`;
        });
        html += '</div></div>';
        container.html(html);
    }
})(jQuery);
