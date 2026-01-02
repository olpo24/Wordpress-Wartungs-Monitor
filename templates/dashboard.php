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
                    $name = !empty($site->name) ? $site->name : 'Unbekannt';
                    $url  = !empty($site->url) ? $site->url : '';
                ?>
                    <tr class="site-row" id="site-row-<?= $site->id ?>" data-id="<?= $site->id ?>">
                        <td class="column-title column-primary">
                            <strong><?= esc_html($name) ?></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?= esc_url($url) ?>" target="_blank">Besuchen</a> | </span>
                                <span class="edit"><a href="#" class="btn-edit-site-meta" data-id="<?= $site->id ?>" data-name="<?= esc_attr($name) ?>" data-url="<?= esc_attr($url) ?>">Einstellungen</a></span>
                            </div>
                        </td>
                        <td class="column-versions" id="version-<?= $site->id ?>">-</td>
                        <td class="column-status" id="status-<?= $site->id ?>">Lade...</td>
                        <td class="column-actions">
                            <button type="button" class="button button-small btn-toggle-details" data-id="<?= $site->id ?>">Details</button>
                        </td>
                    </tr>
                    <tr id="details-row-<?= $site->id ?>" class="inline-edit-row" style="display:none;">
                        <td colspan="4" class="colspanchange">
                            <div class="inline-edit-wrapper">
                                <div class="update-lists-container" id="update-container-<?= $site->id ?>">
                                    </div>
                                <div class="inline-edit-group">
                                    <button class="button button-primary btn-run-bulk-update" data-id="<?= $site->id ?>">Ausgewählte aktualisieren</button>
                                    <button class="button button-secondary btn-close-details" data-id="<?= $site->id ?>">Abbrechen</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="edit-modal" class="wpmm-modal">
    <div class="wpmm-modal-content">
        <div class="modal-header"><h2>Seiteneinstellungen</h2><button class="close-edit-modal">&times;</button></div>
        <div class="modal-body">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <table class="form-table">
                    <tr><td>Name</td><td><input type="text" id="edit-site-name" class="regular-text"></td></tr>
                    <tr><td>URL</td><td><input type="url" id="edit-site-url" class="regular-text"></td></tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary">Speichern</button></p>
            </form>
        </div>
    </div>
</div>
