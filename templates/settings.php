<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Einstellungen</h1>

    <?php 
    $is_added = isset($_GET['wpmm_added']) && $_GET['wpmm_added'] === '1';
    $api_key  = isset($_GET['api_key']) ? sanitize_text_field($_GET['api_key']) : '';
    $site_id  = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;

    if ($is_added && !empty($api_key)): ?>
        <div class="notice notice-success is-dismissible" style="margin-top:20px;">
            <p><strong>Seite erfolgreich hinzugefügt.</strong></p>
            <p>API-Key für das Bridge-Plugin:</p>
            <p><code><?php echo esc_html($api_key); ?></code></p>
            <?php if ($site_id > 0): ?>
                <p>
                    <a href="<?php echo admin_url('admin-ajax.php?action=wpmm_download_bridge&id=' . $site_id); ?>" class="button button-secondary">
                        Bridge-Plugin (ZIP) herunterladen
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="poststuff" style="margin-top:20px;">
        <div class="postbox">
            <div class="postbox-header"><h2 class="hndle">Neue Seite hinzufügen</h2></div>
            <div class="inside">
                <form id="add-site-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="site-name">Anzeigename</label></th>
                            <td><input type="text" id="site-name" class="regular-text" placeholder="z.B. Kundenprojekt XY" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="site-url">Website URL</label></th>
                            <td><input type="url" id="site-url" class="regular-text" placeholder="https://..." required></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Seite registrieren und Key generieren">
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
