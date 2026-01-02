(function($) {
    let updatePerformed = false;
    
    $(document).ready(function() {
        // Sites Status laden
        $('.site-card').each(function() {
            loadSiteStatus($(this).data('id'), $(this));
        });
        
        // Update Button Click
        $(document).on('click', '.btn-update-trigger', function() {
            const card = $(this).closest('.site-card');
            openUpdateModal(card.data('id'), card.find('h3').text());
        });
        
        // Modal schließen
        $(document).on('click', '.close-modal', function() {
            closeModal();
        });
        
        // Display Mode wechseln
        $('#set-grid-mode, #set-list-mode').on('click', function() {
            const mode = $(this).attr('id') === 'set-grid-mode' ? 'grid' : 'list';
            setDisplayMode(mode);
        });
        
        // Site hinzufügen
        $('#add-site-form').on('submit', function(e) {
            e.preventDefault();
            addSite();
        });
        
        // Logs leeren
        $('#clear-logs-btn').on('click', function() {
            if (confirm('Wirklich alle Logs unwiderruflich löschen?')) {
                clearLogs();
            }
        });
    });
    
    function loadSiteStatus(id, card) {
        $.ajax({
            url: wpmmData.ajax_url,
            data: {
                action: 'wpmm_get_status',
                nonce: wpmmData.nonce,
                id: id
            },
            success: function(response) {
                if (!response.success) return;
                
                const data = response.data;
                const versionLabel = card.find('.version-label');
                const statusContainer = card.find('.status-container');
                const updateBtn = card.find('.btn-update-trigger');
                
                if (data.version) {
                    versionLabel.text('v' + data.version);
                }
                
                const pCount = data.updates?.plugin_names?.length || 0;
                const tCount = data.updates?.theme_names?.length || 0;
                const hasCore = data.updates?.core_available || false;
                
                let html = '';
                if (hasCore) html += '<span class="badge" style="background:var(--primary)">WP!</span>';
                html += `<span class="badge ${pCount > 0 ? 'update' : 'ok'}">P: ${pCount}</span>`;
                html += `<span class="badge ${tCount > 0 ? 'update' : 'ok'}">T: ${tCount}</span>`;
                
                statusContainer.html(html);
                
                if (pCount > 0 || tCount > 0 || hasCore) {
                    updateBtn.show();
                }
            }
        });
    }
    
    function openUpdateModal(id, name) {
        updatePerformed = false;
        $('#modal-site-name').text('Updates für ' + name);
        $('#update-modal').show();
        $('#modal-loading').show();
        $('#modal-body').hide();
        
        $.ajax({
            url: wpmmData.ajax_url,
            data: {
                action: 'wpmm_get_status',
                nonce: wpmmData.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    renderUpdateList(id, response.data);
                    $('#modal-loading').hide();
                    $('#modal-body').show();
                }
            }
        });
    }
    
    function closeModal() {
        $('#update-modal').hide();
        if (updatePerformed) {
            location.reload();
        }
    }
    
    function renderUpdateList(siteId, data) {
        // Core Updates
        if (data.updates?.core_available) {
            $('#core-update-section').html(`
                <div class="update-item" id="up-core-main">
                    <strong>🚀 WordPress Core Update verfügbar!</strong>
                    <button onclick="wpmmRunCoreUpdate(${siteId})" class="btn btn-primary">Update</button>
                </div>
            `);
        }
        
        // Plugin Updates
        let pHtml = '<h4>Plugins</h4>';
        if (data.updates?.plugin_names?.length) {
            data.updates.plugin_names.forEach(slug => {
                const safeId = btoa(slug).replace(/=/g, '');
                pHtml += `
                    <div class="update-item" id="up-plugin-${safeId}">
                        <span>${slug}</span>
                        <button onclick="wpmmRunUpdate(${siteId}, 'plugin', '${slug}')" class="btn btn-primary">Update</button>
                    </div>
                `;
            });
        }
        $('#plugin-update-section').html(pHtml);
        
        // Theme Updates
        let tHtml = '<h4>Themes</h4>';
        if (data.updates?.theme_names?.length) {
            data.updates.theme_names.forEach(slug => {
                const safeId = btoa(slug).replace(/=/g, '');
                tHtml += `
                    <div class="update-item" id="up-theme-${safeId}">
                        <span>${slug}</span>
                        <button onclick="wpmmRunUpdate(${siteId}, 'theme', '${slug}')" class="btn btn-primary">Update</button>
                    </div>
                `;
            });
        }
        $('#theme-update-section').html(tHtml);
    }
    
    // Globale Funktionen für onclick
    window.wpmmRunUpdate = function(id, type, slug) {
        const safeId = btoa(slug).replace(/=/g, '');
        const item = $(`#up-${type}-${safeId}`);
        const btn = item.find('button');
        
        btn.html('<span class="spinner"></span>').prop('disabled', true);
        
        $.ajax({
            url: wpmmData.ajax_url,
            method: 'POST',
            data: {
                action: `wpmm_update_${type}`,
                nonce: wpmmData.nonce,
                id: id,
                slug: slug
            },
            success: function(response) {
                if (response.success) {
                    btn.html('✅').css('background', 'var(--success)');
                    updatePerformed = true;
                } else {
                    btn.html('❌').css('background', 'var(--danger)');
                }
            }
        });
    };
    
    window.wpmmRunCoreUpdate = function(id) {
        if (!confirm('WordPress Core Update durchführen?')) return;
        
        const btn = $('#up-core-main button');
        btn.html('<span class="spinner"></span> Updating...').prop('disabled', true);
        
        $.ajax({
            url: wpmmData.ajax_url,
            method: 'POST',
            data: {
                action: 'wpmm_update_core',
                nonce: wpmmData.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    btn.html('✅ Erfolgreich').css('background', 'var(--success)');
                    updatePerformed = true;
                } else {
                    btn.html('❌ Fehler').css('background', 'var(--danger)');
                }
            }
        });
    };
    
    function setDisplayMode(mode) {
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_set_display_mode',
            nonce: wpmmData.nonce,
            mode: mode
        }, function() {
            location.reload();
        });
    }
    
    function addSite() {
        const name = $('#site-name').val();
        const url = $('#site-url').val();
        
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_add_site',
            nonce: wpmmData.nonce,
            name: name,
            url: url
        }, function(response) {
            if (response.success) {
                alert('API Key: ' + response.data.api_key);
                location.reload();
            }
        });
    }
    
    function clearLogs() {
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_clear_logs',
            nonce: wpmmData.nonce
        }, function() {
            location.reload();
        });
    }
    
})(jQuery);
