<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">WP Maintenance Monitor</h1>
    <a href="admin.php?page=wp-maintenance-monitor-settings" class="page-title-action">Seite hinzufügen</a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <th>Website</th>
                <th>Versionen</th>
                <th>Updates</th>
                <th style="text-align:right">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sites as $site): ?>
            <tr class="site-row" data-id="<?= $site->id ?>">
                <td class="column-primary">
                    <strong><?= esc_html($site->name) ?></strong>
                    <div class="row-actions">
                        <span><a href="<?= esc_url($site->url) ?>" target="_blank">Besuchen</a> | </span>
                        <span class="login"><a href="#" class="btn-sso-login" data-id="<?= $site->id ?>">Login</a> | </span>
                        <span><a href="#" class="btn-edit-site-meta" data-id="<?= $site->id ?>" data-name="<?= esc_attr($site->name) ?>" data-url="<?= esc_attr($site->url) ?>">Einstellungen</a></span>
                    </div>
                </td>
                <td id="version-<?= $site->id ?>">-</td>
                <td id="status-<?= $site->id ?>">Lade...</td>
                <td style="text-align:right">
                    <button class="button button-small btn-toggle-details" data-id="<?= $site->id ?>">Details</button>
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
</div>

<div id="edit-modal" class="wpmm-modal" style="display:none;">
    <div class="wpmm-modal-content">
        <div class="modal-header"><h2>Seiteneinstellungen</h2><button class="close-edit-modal">&times;</button></div>
        <div class="modal-body">
            <form id="edit-site-form">
                <input type="hidden" id="edit-site-id">
                <table class="form-table">
                    <tr><th>Name</th><td><input type="text" id="edit-site-name" class="regular-text"></td></tr>
                    <tr><th>URL</th><td><input type="url" id="edit-site-url" class="regular-text"></td></tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Speichern</button>
                    <button type="button" class="button btn-delete-site" style="color:red">Löschen</button>
                </p>
            </form>
        </div>
    </div>
</div>
