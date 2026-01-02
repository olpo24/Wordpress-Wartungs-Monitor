(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // Initialer Status-Check
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // Toggle Details-Bereich
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

        // Update ausführen (Core, Plugin oder Theme)
        $(document).on('click', '.btn-run-update', function() {
            const btn = $(this);
            const id = btn.data('id');
            const type = btn.data('type');
            const slug = btn.data('slug');

            btn.prop('disabled', true).text('⏳ Update läuft...');

            $.post(wpmmData.ajax_url, {
                action: 'wpmm_execute_update',
                nonce: wpmmData.nonce,
                id: id,
                update_type: type,
                slug: slug
            }, function(r) {
                if(r.success && r.data.success) {
                    btn.text('✅ Fertig').css('background', '#46b450');
                    setTimeout(() => loadSiteStatus(id), 3000);
                } else {
                    btn.text('❌ Fehler').prop('disabled', false);
                    console.error("Update fehlgeschlagen:", r);
                }
            });
        });

        // Modal Edit/Delete Logik
        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        $('.close-edit-modal').on('click', function() { $('#edit-modal').hide(); });
    });

    function loadSiteStatus(id) {
        $(`#status-${id}`).text('Wird geprüft...');
        $.post(wpmmData.ajax_url, { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id }, function(r) {
            if(r.success && r.data && r.data.updates) {
                siteDataCache[id] = r.data;
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version || '-');
                
                let badgeHtml = `
                    <span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>
                    <span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>
                `;
                if(c.core > 0) badgeHtml += `<span class="cluster-badge has-updates" style="background:#d64e07;color:#white;">CORE</span>`;
                
                $(`#status-${id}`).html(badgeHtml);
            } else {
                $(`#status-${id}`).html('<span style="color:red;">API-Fehler</span>');
            }
        });
    }

    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $(`#update-container-${id}`);
        if(!data) return;

        let html = '<div class="wpmm-details-grid">';

        // 1. WordPress Core
        if(data.updates.counts.core > 0) {
            html += `
                <div class="update-section core-box">
                    <h4>WordPress Core</h4>
                    <p>Ein neues WordPress-Update ist verfügbar.</p>
                    <button class="button button-primary btn-run-update" data-id="${id}" data-type="core">WordPress jetzt aktualisieren</button>
                </div>`;
        }

        // 2. Plugins
        html += '<div class="update-section"><h4>Plugins</h4>';
        if(data.updates.plugin_names.length > 0) {
            data.updates.plugin_names.forEach(slug => {
                const name = slug.split('/')[0].replace(/-/g, ' ');
                html += `
                    <div class="update-row">
                        <span>${name}</span>
                        <button class="button button-small btn-run-update" data-id="${id}" data-type="plugin" data-slug="${slug}">Update</button>
                    </div>`;
            });
        } else { html += '<p>Alle Plugins aktuell.</p>'; }
        html += '</div>';

        // 3. Themes
        html += '<div class="update-section"><h4>Themes</h4>';
        if(data.updates.theme_names && data.updates.theme_names.length > 0) {
            data.updates.theme_names.forEach(slug => {
                html += `
                    <div class="update-row">
                        <span>${slug}</span>
                        <button class="button button-small btn-run-update" data-id="${id}" data-type="theme" data-slug="${slug}">Update</button>
                    </div>`;
            });
        } else { html += '<p>Alle Themes aktuell.</p>'; }
        html += '</div></div>';

        container.html(html);
    }

})(jQuery);
