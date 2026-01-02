(function($) {
    $(document).ready(function() {
        // Status für alle Karten initialisieren
        $('.site-card').each(function() {
            loadSiteStatus($(this).data('id'), $(this));
        });

        // Modals schließen
        $(document).on('click', '.close-modal, .close-edit-modal', function() {
            $('.wpmm-modal').hide();
        });

        // Seite hinzufügen (AJAX mit stabilem Redirect)
        $('#add-site-form').on('submit', function(e) {
            e.preventDefault();
            const siteName = $('#site-name').val();
            const siteUrl = $('#site-url').val();
            
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_add_site',
                nonce: wpmmData.nonce,
                name: siteName,
                url: siteUrl
            }, function(response) {
                if (response.success && response.data) {
                    const baseUrl = window.location.origin + window.location.pathname;
                    const params = new URLSearchParams(window.location.search);
                    params.set('wpmm_added', '1');
                    params.set('api_key', response.data.api_key);
                    params.set('site_id', response.data.site_id);
                    
                    window.location.href = baseUrl + '?' + params.toString();
                } else {
                    alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
                }
            });
        });

        // Bearbeiten-Modal öffnen
        $(document).on('click', '.btn-edit-site', function() {
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        // Seite aktualisieren
        $('#edit-site-form').on('submit', function(e) {
            e.preventDefault();
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_update_site',
                nonce: wpmmData.nonce,
                id: $('#edit-site-id').val(),
                name: $('#edit-site-name').val(),
                url: $('#edit-site-url').val()
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        });

        // Seite löschen
        $(document).on('click', '.btn-delete-site', function(e) {
            e.preventDefault();
            if (confirm('Diese Seite wirklich löschen?')) {
                $.post(wpmmData.ajax_url, {
                    action: 'wpmm_delete_site',
                    nonce: wpmmData.nonce,
                    id: $('#edit-site-id').val()
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
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
                    
                    // Berechne Gesamtanzahl der Updates aus deiner API-Struktur
                    let totalUpdates = 0;
                    if (data.updates && data.updates.counts) {
                        totalUpdates = parseInt(data.updates.counts.plugins || 0) + 
                                       parseInt(data.updates.counts.themes || 0) + 
                                       parseInt(data.updates.counts.core || 0);
                    }
                    
                    let html = `<div style="margin-bottom:8px; color:#646970; font-size:12px;">WP ${data.version || '??'}</div>`;
                    
                    if (totalUpdates > 0) {
                        html += `<span class="wpmm-badge updates-msg">${totalUpdates} Updates verfügbar</span>`;
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
