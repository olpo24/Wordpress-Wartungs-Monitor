<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="page-title-action">Seite hinzufügen</a>
    <hr class="wp-header-end">

    <?php if (empty($sites)): ?>
        <div class="notice notice-info"><p>Keine Seiten konfiguriert.</p></div>
    <?php else: ?>
        <div class="site-grid">
            <?php foreach ($sites as $site): 
                $name = isset($site->name) ? $site->name : (isset($site->site_name) ? $site->site_name : 'Unbekannt');
                $url  = isset($site->url) ? $site->url : (isset($site->site_url) ? $site->site_url : '');
            ?>
                <div class="site-card" data-id="<?= $site->id ?>">
                    <div class="card-header">
                        <div>
                            <h3><?= esc_html($name) ?></h3>
                            <?php if ($url): ?>
                                <a href="<?= esc_url($url) ?>" target="_blank" class="site-url"><?= esc_url($url) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-content">
                        <span class="description">Status wird geladen...</span>
                    </div>
                    <div class="card-actions">
                        <button class="button button-small btn-update-trigger" style="display:none;">Updates</button>
                        <button class="button button-small btn-edit-site" 
                                data-id="<?= $site->id ?>" 
                                data-name="<?= esc_attr($name) ?>" 
                                data-url="<?= esc_attr($url) ?>">Details / Löschen</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="edit-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2>Seitendetails</h2>
            <button class="close-edit-modal" style="border:none; background:none; cursor:pointer; font-size:20px;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <table class="form-table">
                    <tr>
                        <td><label>Name</label></td>
                        <td><input type="text" id="edit-site-name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <td><label>URL</label></td>
                        <td><input type="url" id="edit-site-url" class="regular-text" required></td>
                    </tr>
                </table>
                <div style="margin-top:20px; display:flex; justify-content: space-between; align-items: center;">
                    <button type="submit" class="button button-primary">Änderungen speichern</button>
                    <button type="button" class="button btn-delete-site" style="color:#d63638; border-color:#d63638;">
                        Löschen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
