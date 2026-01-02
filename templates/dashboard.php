<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="page-title-action">Seite hinzufügen</a>
    <hr class="wp-header-end">

    <?php if (empty($sites)): ?>
        <div class="notice notice-info"><p>Keine Seiten zur Überwachung konfiguriert.</p></div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped posts" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary">Website</th>
                    <th scope="col" class="manage-column">Versionen</th>
                    <th scope="col" class="manage-column column-status">Updates</th>
                    <th scope="col" class="manage-column column-actions">Aktionen</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php foreach ($sites as $site): 
                    $name = !empty($site->name) ? $site->name : (!empty($site->site_name) ? $site->site_name : 'Unbekannt');
                    $url  = !empty($site->url) ? $site->url : (!empty($site->site_url) ? $site->site_url : '');
                ?>
                    <tr class="site-row" data-id="<?= $site->id ?>">
                        <td class="column-title column-primary">
                            <strong><a href="#" class="row-title btn-edit-site" 
                                data-id="<?= $site->id ?>" 
                                data-name="<?= esc_attr($name) ?>" 
                                data-url="<?= esc_attr($url) ?>"><?= esc_html($name) ?></a></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?= esc_url($url) ?>" target="_blank">Website besuchen</a> | </span>
                                <span class="edit"><a href="#" class="btn-edit-site" data-id="<?= $site->id ?>" data-name="<?= esc_attr($name) ?>" data-url="<?= esc_attr($url) ?>">Bearbeiten</a></span>
                            </div>
                        </td>
                        <td id="version-<?= $site->id ?>">
                            <span class="description">Lade...</span>
                        </td>
                        <td class="column-status" id="status-<?= $site->id ?>">
                            <span class="description">Prüfe Updates...</span>
                        </td>
                        <td class="column-actions">
                            <button class="button button-small btn-update-trigger" style="display:none;">Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="edit-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2>Seiteneinstellungen</h2>
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
                <div style="margin-top:20px; display:flex; justify-content: space-between;">
                    <button type="submit" class="button button-primary">Speichern</button>
                    <button type="button" class="button btn-delete-site" style="color:#d63638; border-color:#d63638;">Löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
