<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="<?= admin_url('admin.php?page=wp-maintenance-monitor-settings') ?>" class="page-title-action">Seite hinzufügen</a>
    <hr class="wp-header-end">

    <?php if (empty($sites)): ?>
        <div class="notice notice-info"><p>Keine Seiten konfiguriert.</p></div>
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
            <tbody>
                <?php foreach ($sites as $site): 
                    $name = !empty($site->name) ? $site->name : 'Unbekannt';
                    $url  = !empty($site->url) ? $site->url : '';
                ?>
                    <tr class="site-row" data-id="<?= $site->id ?>">
                        <td class="column-title column-primary">
                            <strong><?= esc_html($name) ?></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?= esc_url($url) ?>" target="_blank">Besuchen</a> | </span>
                                <span class="login"><a href="#" class="btn-sso-login" data-id="<?= $site->id ?>">Login</a> | </span>
                                <span class="edit"><a href="#" class="btn-edit-site-meta" data-id="<?= $site->id ?>" data-name="<?= esc_attr($name) ?>" data-url="<?= esc_attr($url) ?>">Einstellungen</a></span>
                            </div>
                        </td>
                        <td id="version-<?= $site->id ?>">-</td>
                        <td id="status-<?= $site->id ?>">Lade...</td>
                        <td class="column-actions">
                            <button type="button" class="button button-small btn-toggle-details" data-id="<?= $site->id ?>">Details</button>
                        </td>
                    </tr>
                    <tr id="details-row-<?= $site->id ?>" class="inline-edit-row" style="display:none;">
                        <td colspan="4">
                            <div class="inline-edit-wrapper">
                                <div class="update-lists-container" id="update-container-<?= $site->id ?>"></div>
                                <div style="margin-top:10px;">
                                    <button class="button button-primary btn-run-bulk-update" data-id="<?= $site->id ?>">Ausgewählte aktualisieren</button>
                                    <button class="button btn-close-details" data-id="<?= $site->id ?>">Schließen</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="edit-modal" class="wpmm-modal" style="display:none;">
    <div class="wpmm-modal-content">
        <div class="modal-header">
            <h2>Seiteneinstellungen bearbeiten</h2>
            <button class="close-edit-modal" style="border:none; background:none; cursor:pointer; font-size:24px;">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <table class="form-table">
                    <tr><th>Name</th><td><input type="text" id="edit-site-name" class="regular-text" required></td></tr>
                    <tr><th>URL</th><td><input type="url" id="edit-site-url" class="regular-text" required></td></tr>
                </table>
                <div style="margin-top:20px; display:flex; justify-content: space-between;">
                    <button type="submit" class="button button-primary">Speichern</button>
                    <button type="button" class="button btn-delete-site" style="color:#d63638; border-color:#d63638;">Seite löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
