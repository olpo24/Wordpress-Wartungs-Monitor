<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Dashboard</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr><th>Website</th><th>WordPress</th><th>Updates</th><th style="text-align:right">Aktionen</th></tr>
        </thead>
        <tbody>
            <?php foreach ($sites as $site): ?>
            <tr class="site-row" data-id="<?= $site->id ?>">
                <td>
                    <strong><?= esc_html($site->name) ?></strong>
                    <div class="row-actions">
                        <span><a href="<?= esc_url($site->url) ?>" target="_blank">Besuchen</a> | </span>
                        <span class="login"><a href="#" class="btn-sso-login" data-id="<?= $site->id ?>">Login</a> | </span>
                        <span><a href="#" class="btn-edit-site-meta" data-id="<?= $site->id ?>" data-name="<?= esc_attr($site->name) ?>" data-url="<?= esc_attr($site->url) ?>">Einstellungen</a></span>
                    </div>
                </td>
                <td id="version-<?= $site->id ?>">-</td>
                <td id="status-<?= $site->id ?>">Lade...</td>
                <td style="text-align:right"><button class="button btn-toggle-details" data-id="<?= $site->id ?>">Details</button></td>
            </tr>
            <tr id="details-row-<?= $site->id ?>" class="inline-edit-row" style="display:none;">
                <td colspan="4"><div class="inline-edit-wrapper"><div id="update-container-<?= $site->id ?>"></div></div></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="edit-modal" class="wpmm-modal" style="display:none;">
    <div class="wpmm-modal-content">
        <div class="modal-header"><h2>Edit</h2><button class="close-edit-modal">&times;</button></div>
        <form id="edit-site-form">
            <input type="hidden" id="edit-site-id">
            <p><label>Name</label><br><input type="text" id="edit-site-name" class="regular-text"></p>
            <p><label>URL</label><br><input type="url" id="edit-site-url" class="regular-text"></p>
            <button type="submit" class="button button-primary">Save</button>
            <button type="button" class="button btn-delete-site" style="color:red">Delete</button>
        </form>
    </div>
</div>
