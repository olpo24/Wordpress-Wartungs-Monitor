<?php
/**
 * Settings Template - Native Design
 */
$display_mode = get_user_meta(get_current_user_id(), 'wpmm_display_mode', true) ?: 'grid';
$show_success = isset($_GET['wpmm_added']) && $_GET['wpmm_added'] == '1';
$new_api_key  = isset($_GET['api_key']) ? sanitize_text_field($_GET['api_key']) : '';
$new_site_id  = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;
?>

<div class="wrap wpmm-container">
    <h1>Einstellungen</h1>
    
    <?php if ($show_success && !empty($new_api_key)): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Seite erfolgreich hinzugefügt.</strong></p>
            <p>API-Key: <code><?= esc_html($new_api_key) ?></code></p>
            <p>
                <a href="<?= admin_url('admin-ajax.php?action=wpmm_download_bridge&id=' . $new_site_id) ?>" class="button button-secondary">
                    Bridge-Plugin herunterladen
                </a>
            </p>
        </div>
    <?php endif; ?>

    <div id="poststuff">
        <div class="postbox">
            <div class="postbox-header"><h2 class="hndle">Ansicht</h2></div>
            <div class="inside">
                <button id="set-grid-mode" class="button <?= $display_mode === 'grid' ? 'button-primary' : '' ?>">Grid</button>
                <button id="set-list-mode" class="button <?= $display_mode === 'list' ? 'button-primary' : '' ?>">Liste</button>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header"><h2 class="hndle">Neue Seite hinzufügen</h2></div>
            <div class="inside">
                <form id="add-site-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Name</th>
                            <td><input type="text" id="site-name" class="regular-text" placeholder="z.B. Projekt A" required></td>
                        </tr>
                        <tr>
                            <th scope="row">URL</th>
                            <td><input type="url" id="site-url" class="regular-text" placeholder="https://..." required></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Seite speichern">
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
