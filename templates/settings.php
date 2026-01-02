<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Einstellungen</h1>

    <?php if (isset($_GET['wpmm_added'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Seite erfolgreich hinzugefügt.</strong></p>
            <p>API-Key: <code><?= esc_html($_GET['api_key']) ?></code></p>
            <p><a href="<?= admin_url('admin-ajax.php?action=wpmm_download_bridge&id=' . intval($_GET['site_id'])) ?>" class="button button-secondary">Bridge-Plugin (ZIP) herunterladen</a></p>
        </div>
    <?php endif; ?>

    <div id="poststuff">
        <div class="postbox">
            <div class="postbox-header"><h2 class="hndle">Neue Seite hinzufügen</h2></div>
            <div class="inside">
                <form id="add-site-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="site-name">Name</label></th>
                            <td><input type="text" id="site-name" class="regular-text" placeholder="z.B. Kundenprojekt XY" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="site-url">URL</label></th>
                            <td><input type="url" id="site-url" class="regular-text" placeholder="https://beispiel.de" required></td>
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
