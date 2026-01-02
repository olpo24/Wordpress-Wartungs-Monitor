(function($) {
    // Speicher für geladene Daten, um unnötige Requests zu vermeiden
    let siteDataCache = {};

    $(document).ready(function() {
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'));
        });

        // Details-Bereich umschalten (Inline Edit)
        $(document).on('click', '.btn-toggle-details', function() {
            const id = $(this).data('id');
            const detailsRow = $('#details-row-' + id);
            
            if (detailsRow.is(':visible')) {
                detailsRow.hide();
            } else {
                renderUpdateLists(id);
                detailsRow.show();
            }
        });

        $(document).on('click', '.btn-close-details', function() {
            $('#details-row-' + $(this).data('id')).hide();
        });

        // Bulk Update Funktion
        $(document).on('click', '.btn-run-bulk-update', function() {
            const id = $(this).data('id');
            const container = $('#update-container-' + id);
            const selectedItems = container.find('input:checked');
            
            if (selectedItems.length === 0) {
                alert('Bitte wähle mindestens ein Update aus.');
                return;
            }

            if (!confirm('Ausgewählte Updates jetzt durchführen?')) return;

            processBulkUpdate(id, selectedItems);
        });

        // Meta-Edit Modal (Name/URL)
        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        $('.close-edit-modal').on('click', function() { $('.wpmm-modal').hide(); });
    });

    function loadSiteStatus(id) {
        $.ajax({
            url: wpmmData.ajax_url,
            data: { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id },
            success: function(response) {
                if (response.success && response.data) {
                    siteDataCache[id] = response.data;
                    updateRowDisplay(id, response.data);
                }
            }
        });
    }

    function updateRowDisplay(id, data) {
        const c = data.updates.counts;
        $(`#version-${id}`).text(`WP: ${data.version}`);
        
        let html = '<div class="update-cluster">';
        html += `<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>`;
        html += `<span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`;
        html += `<span class="cluster-badge ${c.core > 0 ? 'has-updates' : ''}">C: ${c.core}</span>`;
        html += '</div>';
        $(`#status-${id}`).html(html);
    }

    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        const container = $('#update-container-' + id);
        if (!data) return;

        let html = '';

        // Core Update
        if (data.updates.core_available) {
            html += '<div class="update-list-group"><h4>WordPress Core</h4>';
            html += `<div class="update-item"><input type="checkbox" class="core-upd" value="core"> Core Update verfügbar</div></div>`;
        }

        // Plugins
        if (data.updates.plugin_names && data.updates.plugin_names.length > 0) {
            html += '<div class="update-list-group"><h4>Plugins</h4>';
            data.updates.plugin_names.forEach(p => {
                html += `<div class="update-item"><input type="checkbox" class="plugin-upd" value="${p}"> ${p.split('/')[0]}</div>`;
            });
            html += '</div>';
        }

        // Themes
        if (data.updates.theme_names && data.updates.theme_names.length > 0) {
            html += '<div class="update-list-group"><h4>Themes</h4>';
            data.updates.theme_names.forEach(t => {
                html += `<div class="update-item"><input type="checkbox" class="theme-upd" value="${t}"> ${t}</div>`;
            });
            html += '</div>';
        }

        container.html(html || '<p>Keine Updates zum Anzeigen.</p>');
    }

    async function processBulkUpdate(id, items) {
        const row = $('#details-row-' + id);
        row.addClass('wpmm-loading');

        for (let item of items) {
            const $item = $(item);
            const value = $item.val();
            let action = '';

            if ($item.hasClass('plugin-upd')) action = 'wpmm_update_plugin';
            else if ($item.hasClass('theme-upd')) action = 'wpmm_update_theme';
            else if ($item.hasClass('core-upd')) action = 'wpmm_update_core';

            $item.parent().append(' <span>...</span>');

            try {
                await $.post(wpmmData.ajax_url, {
                    action: action,
                    nonce: wpmmData.nonce,
                    id: id,
                    item: value
                });
                $item.parent().find('span').text(' ✅');
                $item.prop('checked', false).prop('disabled', true);
            } catch (e) {
                $item.parent().find('span').text(' ❌');
            }
        }

        row.removeClass('wpmm-loading');
        loadSiteStatus(id); // Status nach Updates erneuern
    }

})(jQuery);
