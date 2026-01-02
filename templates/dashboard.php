<?php
/**
 * Dashboard Template - Native Design
 */
?>

<div class="wrap wpmm-container">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="page-title-action">Seite hinzufügen</a>
    
    <hr class="wp-header-end">
    
    <?php if (empty($sites)): ?>
        <div class="notice notice-info">
            <p>Keine Seiten zur Überwachung konfiguriert. <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>">Jetzt erste Seite hinzufügen.</a></p>
        </div>
    <?php else: ?>

        <div class="site-grid <?= $display_mode === 'list' ? 'mode-list' : '' ?>">
            <?php foreach ($sites as $site): ?>
                <div class="site-card" data-id="<?= $site->id ?>">
                    <div class="card-header">
                        <h3><?= esc_html($site->site_name) ?></h3>
                        <code style="font-size: 10px;"><?= esc_url($site->site_url) ?></code>
                    </div>
                    
                    <div class="card-content" id="status-<?= $site->id ?>">
                        <p class="description">Lade Status...</p>
                    </div>

                    <div class="card-actions" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #f0f0f1;">
                        <button class="button button-small btn-update-trigger" style="display:none;">Updates prüfen</button>
                        <button class="button button-small btn-edit-site" 
                                data-id="<?= $site->id ?>" 
                                data-name="<?= esc_attr($site->site_name) ?>" 
                                data-url="<?= esc_attr($site->site_url) ?>">Bearbeiten</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="edit-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2>Seite bearbeiten</h2>
            <button class="close-edit-modal" style="background:none; border:none; cursor:pointer; font-size:20px;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <p>
                    <label>Name der Seite</label><br>
                    <input type="text" id="edit-site-name" class="regular-text" required>
                </p>
                <p>
                    <label>URL der Seite</label><br>
                    <input type="url" id="edit-site-url" class="regular-text" required>
                </p>
                <div style="margin-top:20px;">
                    <button type="submit" class="button button-primary">Speichern</button>
                    <button type="button" class="button btn-delete-site" style="color: #d63638; border-color: #d63638;">Seite löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="update-modal" class="wpmm-modal">
    <div class="wpmm-modal-content" style="width: 700px;">
        <div class="modal-header">
            <h2 id="modal-site-name">Updates</h2>
            <button class="close-modal" style="background:none; border:none; cursor:pointer; font-size:20px;">&times;</button>
        </div>
        <div class="modal-body" id="update-modal-body">
            </div>
    </div>
</div>
