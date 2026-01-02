(function($) {
    $(document).ready(function() {
        // Status laden
        $('.site-card').each(function() {
            loadSiteStatus($(this).data('id'), $(this));
        });

        // Modals
        $(document).on('click', '.btn-update-trigger', function() {
            const card = $(this).closest('.site-card');
            openUpdateModal(card.data('id'), card.find('h3').text());
        });

        $(document).on('click', '.close-modal, .close-edit-modal', function() {
            $('.wpmm-modal').hide();
        });

        // Add Site
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
                }
            });
        });
    });

    function loadSiteStatus(id, card) {
        const statusDiv = card.find('.card-content');
        $.ajax({
            url: wpmmData.ajax_url,
            data: { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    let data = response.data;
                    let html = `<p>WP: ${data.core_version} | PHP: ${data.php_version}</p>`;
                    
                    if (data.updates.total > 0) {
                        html += `<span class="status-badge updates-available">${data.updates.total} Updates verfügbar</span>`;
                        card.find('.btn-update-trigger').show();
                    } else {
                        html += `<span class="status-badge">Aktuell</span>`;
                    }
                    statusDiv.html(html);
                } else {
                    statusDiv.html('<span class="status-badge" style="color:red;">Verbindung fehlgeschlagen</span>');
                }
            }
        });
    }

    function openUpdateModal(id, name) {
        $('#modal-site-name').text('Updates für ' + name);
        $('#update-modal-body').html('Lade Update-Details...');
        $('#update-modal').show();
        
        // Hier würde der AJAX-Call für die Details folgen (logs.php Logik)
    }

})(jQuery);
