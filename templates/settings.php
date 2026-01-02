<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>WP Maintenance Monitor - Einstellungen</h1>

    <?php 
    $is_added = isset($_GET['wpmm_added']) && $_GET['wpmm_added'] === '1';
    $api_key  = isset($_GET['api_key']) ? sanitize_text_field($_GET['api_key']) : '';
    ?>

    <?php if ($is_added && !empty($api_key)): ?>
        <div class="notice notice-success is-dismissible" style="margin-top:20px;">
            <p><strong>Seite erfolgreich hinzugefügt!</strong></p>
            <p>Kopiere diesen API-Key in das Bridge-Plugin auf der Zielseite:</p>
            <p><code style="background: #e5e5e5; padding: 5px 10px; font-size: 1.2em;"><?php echo esc_html($api_key); ?></code></p>
            <p><a href="admin.php?page=wp-maintenance-monitor" class="button button-primary">Zum Dashboard</a></p>
        </div>
    <?php endif; ?>

    <div id="poststuff" style="margin-top:20px;">
        <div class="postbox">
            <div class="postbox-header"><h2 class="hndle">Neue Website registrieren</h2></div>
            <div class="inside">
                <form id="add-site-form" method="POST">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="site-name">Anzeigename</label></th>
                            <td><input type="text" id="site-name" class="regular-text" placeholder="z.B. Kundenprojekt XY" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="site-url">Website URL</label></th>
                            <td><input type="url" id="site-url" class="regular-text" placeholder="https://..." required>
                            <p class="description">Geben Sie die vollständige URL inkl. https:// ein.</p></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Seite registrieren">
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
