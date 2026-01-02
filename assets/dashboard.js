(function($) {
    let siteDataCache = {};
    $(document).ready(function() {
        $('.site-row').each(function() { loadSiteStatus($(this).data('id')); });

        $('#add-site-form').on('submit', function(e) {
            e.preventDefault();
            $.post(wpmmData.ajax_url, {
                action: 'wpmm_add_site',
                nonce: wpmmData.nonce,
                name: $('#site-name').val(),
                url: $('#site-url').val()
            }, function(r) {
                if(r.success) window.location.href = 'admin.php?page=wp-maintenance-monitor-settings&wpmm_added=1&api_key=' + r.data.api_key;
            });
        });

        $(document).on('click', '.btn-sso-login', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const link = $(this);
            link.text('⏳...');
            $.post(wpmmData.ajax_url, { action: 'wpmm_get_login_url', nonce: wpmmData.nonce, id: id }, function(r) {
                link.text('Login');
                if(r.success) window.open(r.data.login_url, '_blank');
            });
        });

        $(document).on('click', '.btn-toggle-details', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#details-row-' + id).toggle();
            if($('#details-row-' + id).is(':visible')) renderUpdateLists(id);
        });

        $(document).on('click', '.btn-edit-site-meta', function(e) {
            e.preventDefault();
            $('#edit-site-id').val($(this).data('id'));
            $('#edit-site-name').val($(this).data('name'));
            $('#edit-site-url').val($(this).data('url'));
            $('#edit-modal').show();
        });

        $('.close-edit-modal').on('click', function() { $('#edit-modal').hide(); });

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

        $(document).on('click', '.btn-run-bulk-update', function() {
            const id = $(this).data('id');
            const items = $('#update-container-' + id).find('input:checked');
            if(items.length > 0) processBulkUpdate(id, items);
        });
    });

    function loadSiteStatus(id) {
        $.post(wpmmData.ajax_url, { action: 'wpmm_get_status', nonce: wpmmData.nonce, id: id }, function(r) {
            if(r.success) {
                siteDataCache[id] = r.data;
                const c = r.data.updates.counts;
                $(`#version-${id}`).text(r.data.version);
                $(`#status-${id}`).html(`<span class="cluster-badge ${c.plugins > 0 ? 'has-updates' : ''}">P: ${c.plugins}</span><span class="cluster-badge ${c.themes > 0 ? 'has-updates' : ''}">T: ${c.themes}</span>`);
            }
        });
    }

    function renderUpdateLists(id) {
        const data = siteDataCache[id];
        let html = '';
        if(data.updates.plugin_names) {
            data.updates.plugin_names.forEach(p => html += `<div><input type="checkbox" class="plugin-upd" value="${p}"> ${p.split('/')[0]}</div>`);
        }
        $('#update-container-' + id).html(html || 'Alles aktuell');
    }

    async function processBulkUpdate(id, items) {
        for(let item of items) {
            const $i = $(item);
            await $.post(wpmmData.ajax_url, { action: 'wpmm_update_plugin', nonce: wpmmData.nonce, id: id, item: $i.val() });
            $i.parent().append(' ✅');
        }
        loadSiteStatus(id);
    }
})(jQuery);
