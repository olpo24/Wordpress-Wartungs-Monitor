(function($) {
    $(document).ready(function() {
        // Status für alle Zeilen laden
        $('.site-row').each(function() {
            loadSiteStatus($(this).data('id'), $(this));
        });

        $(document).on('click', '.close-modal, .close-edit-modal', function() {
            $('.wpmm-modal').hide();
        });

        $(document).on('click', '.btn-edit-site', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        // Update AJAX
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

        // Löschen AJAX
        $(document).on('click', '.btn-delete-site', function() {
            if (confirm('Seite wirklich löschen?')) {
                $.post(wpmmData.ajax_url, {
                    action: 'wpmm_delete_site',
                    nonce: wpmmData.nonce,
                    id: $('#edit-site-id').val()
                }, function() { location.reload(); });
            }
        });
    });

    function loadSiteStatus(id, row) {
        const versionCell = $('#version-' + id);
        const statusCell = $('#status-' + id);

        $.ajax({
            url: wpmmData.ajax_url,
            data: { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    const c = data.updates && data.updates.counts ? data.updates.counts : {plugins:0, themes:0, core:0, translations:0};
                    
                    // Versionen anzeigen
                    versionCell.html(`WP: ${data.version || '??'}`);

                    // Update Cluster bauen
                    let html = '<div class="update-cluster">';
                    
                    // Plugins
                    html += `<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span>`;
                    // Themes
                    html += `<span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`;
                    // Core
                    html += `<span class="cluster-badge ${c.core > 0 ? 'has-updates' : ''}">C: ${c.core}</span>`;
                    // Übersetzungen (falls in API vorhanden, sonst 0)
                    html += `<span class="cluster-badge ${c.translations > 0 ? 'has-updates' : ''}">Ü: ${c.translations || 0}</span>`;
                    
                    html += '</div>';
                    statusCell.html(html);

                    if ((parseInt(c.plugins) + parseInt(c.themes) + parseInt(c.core)) > 0) {
                        row.find('.btn-update-trigger').show();
                    }
                } else {
                    statusCell.html('<span class="cluster-badge" style="color:red;">Offline</span>');
                    versionCell.html('-');
                }
            }
        });
    }
})(jQuery);
