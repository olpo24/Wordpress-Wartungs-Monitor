(function($) {
    $(document).ready(function() {
        // Status für alle Karten initialisieren
        $('.site-card').each(function() {
            loadSiteStatus($(this).data('id'), $(this));
        });

        // Modal schliessen
        $(document).on('click', '.close-modal, .close-edit-modal', function() {
            $('.wpmm-modal').hide();
        });

        // Seite hinzufügen Formular
        $('#add-site-form').on('submit', function(e) {
            e.preventDefault();
            const name = $('#site-name').val();
            const url = $('#site-url').val();
            
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_add_site',
                nonce: wpmmData.nonce,
                name: name,
                url: url
            }, function(response) {
                if (response.success) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('wpmm_added', '1');
                    currentUrl.searchParams.set('api_key', response.data.api_key);
                    currentUrl.searchParams.set('site_id', response.data.site_id);
                    window.location.href = currentUrl.toString();
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            });
        });

        // Seite bearbeiten Modal öffnen
        $(document).on('click', '.btn-edit-site', function() {
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });
    });

    function loadSiteStatus(id, card) {
        const content = card.find('.card-content');
        $.ajax({
            url: wpmmData.ajax_url,
            data: { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    let html = `<div style="margin-bottom:8px;">WP ${data.core_version || '??'} | PHP ${data.php_version || '??'}</div>`;
                    
                    // Fix: Check if updates property exists before reading .total
                    if (data.updates && typeof data.updates.total !== 'undefined' && data.updates.total > 0) {
                        html += `<span class="wpmm-badge updates-msg">${data.updates.total} Updates verfügbar</span>`;
                        card.find('.btn-update-trigger').show();
                    } else {
                        html += `<span class="wpmm-badge">System aktuell</span>`;
                    }
                    content.html(html);
                } else {
                    content.html('<span class="wpmm-badge error-msg">Verbindung fehlgeschlagen</span>');
                }
            },
            error: function() {
                content.html('<span class="wpmm-badge error-msg">Server-Fehler</span>');
            }
        });
    }
})(jQuery);
