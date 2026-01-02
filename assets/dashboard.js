(function($) {
    let siteDataCache = {};

    $(document).ready(function() {
        // Initialer Status-Check
        $('.site-row').each(function() { loadSiteStatus($(this).data('id')); });

        // ONE-CLICK LOGIN (SSO)
        $(document).on('click', '.btn-sso-login', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const link = $(this);
            const originalText = link.text();

            link.text('⏳...').css('pointer-events', 'none');

            $.post(wpmmData.ajax_url, {
                action: 'wpmm_get_login_url',
                nonce: wpmmData.nonce,
                id: id
            }, function(response) {
                link.text(originalText).css('pointer-events', 'auto');
                if (response.success && response.data.login_url) {
                    window.open(response.data.login_url, '_blank');
                } else {
                    alert('Fehler: ' + (response.data.message || 'Login-URL konnte nicht abgerufen werden.'));
                }
            }).fail(function() {
                alert('Server-Fehler beim Login-Versuch.');
                link.text(originalText).css('pointer-events', 'auto');
            });
        });

        // DETAILS TOGGLE (Inline Updates)
        $(document).on('click', '.btn-toggle-details', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const row = $('#details-row-' + id);
            if (row.is(':visible')) {
                row.hide();
            } else {
                renderUpdateLists(id);
                row.show();
            }
        });

        $(document).on('click', '.btn-close-details', function() {
            $('#details-row-' + $(this).data('id')).hide();
        });

        // MODAL SETTINGS
        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').fadeIn(200);
        });

        $('.close-edit-modal').on('click', function() { $('.wpmm-modal').fadeOut(200); });

        $('#edit-site-form').on('submit', function(e) {
            e.preventDefault();
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_update_site',
                nonce: wpmmData.nonce,
                id: $('#edit-site-id').val(),
                name: $('#edit-site-name').val(),
                url: $('#edit-site-url').val()
            }, function() { location.reload(); });
        });

        $(document).on('click', '.btn-delete-site', function() {
            if (confirm('Diese Seite wirklich löschen?')) {
                $.post(wpmmData.ajax_url, {
                    action: 'wpmm_delete_site',
                    nonce: wpmmData.nonce,
                    id: $('#edit-site-id').val()
                }, function() { location.reload(); });
            }
        });

        // BULK UPDATES
        $(document).on('click', '.btn-run-bulk-update', function() {
            const id = $(this).data('id');
            const items = $('#update-container-' + id).find('input:checked');
            if (items.length > 0 && confirm('Ausgewählte Updates jetzt durchführen?')) processBulkUpdate(id, items);
        });
    });

    function loadSiteStatus(id) {
        $.ajax({
            url: wpmmData.ajax_url,
            data: { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id },
            success: function(response) {
                if (response.success && response.data) {
                    siteDataCache[id] = response.data;
                    const c = response.data.updates.counts;
                    $(`#version-${id}`).text(`WP: ${response.data.version}`);
                    $(`#status-${id}`).html(`
                        <span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>
                        <span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>
                        <span class="cluster-badge ${c.core > 0 ? 'has-updates' : ''}">C: ${c.core}</span>
                    `);
                }
            }
        });
    }

    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $('#update-container-' + id);
        if (!data) return;
        let html = '';
        if (data.updates.core_available) html += '<div class="update-list-group"><h4>Core</h4><div class="update-item"><input type="checkbox" class="core-upd" value="core"> WordPress Core</div></div>';
        if (data.updates.plugin_names?.length > 0) {
            html += '<div class="update-list-group"><h4>Plugins</h4>';
            data.updates.plugin_names.forEach(p => html += `<div class="update-item"><input type="checkbox" class="plugin-upd" value="${p}"> ${p.split('/')[0]}</div>`);
            html += '</div>';
        }
        if (data.updates.theme_names?.length > 0) {
            html += '<div class="update-list-group"><h4>Themes</h4>';
            data.updates.theme_names.forEach(t => html += `<div class="update-item"><input type="checkbox" class="theme-upd" value="${t}"> ${t}</div>`);
            html += '</div>';
        }
        container.html(html || '<p>Keine Updates verfügbar.</p>');
    }

    async function processBulkUpdate(id, items) {
        $('#details-row-' + id).addClass('wpmm-loading');
        for (let item of items) {
            const $i = $(item);
            let action = $i.hasClass('plugin-upd') ? 'wpmm_update_plugin' : ($i.hasClass('theme-upd') ? 'wpmm_update_theme' : 'wpmm_update_core');
            $i.parent().append(' <small>...</small>');
            try {
                await $.post(wpmmData.ajax_url, { action: action, nonce: wpmmData.nonce, id: id, item: $i.val() });
                $i.parent().find('small').text(' ✅');
                $i.prop('checked', false).prop('disabled', true);
            } catch (e) { $i.parent().find('small').text(' ❌'); }
        }
        $('#details-row-' + id).removeClass('wpmm-loading');
        loadSiteStatus(id);
    }
})(jQuery);
