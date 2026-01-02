<?php
/**
 * Dashboard Template
 * Zeigt alle verwalteten WordPress-Seiten
 */
?>

<div class="wrap wpmm-container">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="page-title-action">
        ➕ Neue Seite hinzufügen
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (empty($sites)): ?>
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong>Keine Seiten vorhanden.</strong><br>
                Füge deine erste WordPress-Installation hinzu, um Updates zentral zu verwalten.
            </p>
            <p>
                <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="button button-primary">
                    Jetzt Seite hinzufügen
                </a>
            </p>
        </div>
    <?php else: ?>
        <div style="margin: 20px 0; padding: 15px; background: white; border: 1px solid #c3c4c7; border-radius: 4px;">
            <p style="margin: 0;">
                <strong><?= count($sites) ?> Seiten</strong> werden überwacht. 
                <span style="color: #666;">Letzte Aktualisierung: <?= date('d.m.Y H:i') ?> Uhr</span>
            </p>
        </div>
        
        <div class="site-grid <?= $display_mode === 'list' ? 'mode-list' : '' ?>">
            <?php foreach ($sites as $site): ?>
                <div class="site-card" data-id="<?= esc_attr($site->id) ?>">
                    <div class="card-header">
                        <h3><?= esc_html($site->name) ?></h3>
                        <span class="version-label">v?</span>
                    </div>
                    
                    <div style="margin: 10px 0; font-size: 12px; color: #666;">
                        <a href="<?= esc_url($site->url) ?>" target="_blank" style="text-decoration: none;">
                            🔗 <?= esc_html(parse_url($site->url, PHP_URL_HOST)) ?>
                        </a>
                    </div>
                    
                    <div class="status-container">
                        <span class="badge">Lade...</span>
                    </div>
                    
                    <button class="btn-update-trigger" style="display:none;">
                        Updates verwalten 🔄
                    </button>
                    
                    <div style="margin-top: 10px; padding: 8px; background: #f6f7f7; border-radius: 4px; font-size: 11px; color: #666;">
                        Key: ****<?= esc_html(substr($site->api_key, -4)) ?>
                    </div>
                    
                    <div class="card-actions">
                        <a href="<?= esc_url($site->url . '/wp-admin') ?>" target="_blank" class="btn btn-success" title="WordPress Admin öffnen">
                            🔐 Login
                        </a>
                        <button class="btn btn-primary btn-edit-site" data-id="<?= $site->id ?>" data-name="<?= esc_attr($site->name) ?>" data-url="<?= esc_attr($site->url) ?>" title="Bearbeiten">
                            ✏️
                        </button>
                        <button class="btn btn-danger btn-delete-site" data-id="<?= $site->id ?>" title="Löschen">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Update Modal -->
<div id="update-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2 id="modal-site-name">Updates</h2>
            <button class="close-modal">&times;</button>
        </div>
        <div id="modal-loading" style="text-align: center; padding: 40px;">
            <span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
            <p style="margin-top: 10px;">Lade Update-Details...</p>
        </div>
        <div id="modal-body" style="display:none;">
            <div id="core-update-section" style="margin-bottom: 20px;"></div>
            <div id="plugin-update-section" style="margin-bottom: 20px;"></div>
            <div id="theme-update-section" style="margin-bottom: 20px;"></div>
            <hr>
            <button id="update-all-btn" class="button button-primary button-large" style="width: 100%;">
                🚀 Alles aktualisieren
            </button>
        </div>
    </div>
</div>

<!-- Edit Site Modal -->
<div id="edit-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2>Seite bearbeiten</h2>
            <button class="close-edit-modal">&times;</button>
        </div>
        <div style="padding: 20px;">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <table class="form-table">
                    <tr>
                        <th><label for="edit-site-name">Name</label></th>
                        <td>
                            <input type="text" id="edit-site-name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="edit-site-url">URL</label></th>
                        <td>
                            <input type="url" id="edit-site-url" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Speichern</button>
                    <button type="button" class="button close-edit-modal">Abbrechen</button>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Edit Modal
    $('.btn-edit-site').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var url = $(this).data('url');
        
        $('#edit-site-id').val(id);
        $('#edit-site-name').val(name);
        $('#edit-site-url').val(url);
        $('#edit-modal').show();
    });
    
    $('.close-edit-modal').on('click', function() {
        $('#edit-modal').hide();
    });
    
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
    
    // Delete Site
    $('.btn-delete-site').on('click', function() {
        if (!confirm('Diese Seite wirklich löschen?')) return;
        
        var id = $(this).data('id');
        
        $.post(wpmmData.ajax_url, {
            action: 'wpmm_delete_site',
            nonce: wpmmData.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
});
</script>
