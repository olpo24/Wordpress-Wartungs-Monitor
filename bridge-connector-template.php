<?php
/**
 * Plugin Name: WP Dashboard Bridge Connector
 * Plugin URI: https://github.com/yourusername/wp-maintenance-monitor
 * Description: Ermöglicht Status-Abfragen und Remote-Updates via API-Key für das WP Maintenance Monitor Dashboard
 * Version: 3.0.0
 * Author: Dein Name
 * License: GPL v3
 * Text Domain: wp-bridge-connector
 */

if (!defined('ABSPATH')) exit;

/**
 * Admin-Menü für Plugin-Einstellungen
 */
add_action('admin_menu', function() {
    add_options_page(
        'WP Bridge Settings',
        'WP Bridge',
        'manage_options',
        'wp-bridge-connector',
        'wpbc_render_settings_page'
    );
});

/**
 * Einstellungsseite rendern
 */
function wpbc_render_settings_page() {
    // Einstellungen speichern
    if (isset($_POST['bridge_save_key']) && check_admin_referer('wpbc_save_key', 'wpbc_nonce')) {
        $api_key = sanitize_text_field($_POST['bridge_key']);
        update_option('bridge_api_key', $api_key);
        echo '<div class="notice notice-success is-dismissible"><p><strong>API-Key erfolgreich gespeichert!</strong></p></div>';
    }
    
    $current_key = get_option('bridge_api_key', '{{API_KEY}}');
    $has_key = !empty($current_key) && $current_key !== '{{API_KEY}}';
    $is_placeholder = ($current_key === '{{API_KEY}}');
    ?>
    <div class="wrap">
        <h1>🔗 WP Dashboard Bridge Connector</h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>API-Key Konfiguration</h2>
            
            <?php if ($is_placeholder): ?>
                <div class="notice notice-info inline">
                    <p><strong>ℹ️ API-Key aus dem Dashboard hier eintragen</strong><br>
                    Ersetze den Platzhalter durch den echten API-Key von deinem WP Maintenance Monitor Dashboard.</p>
                </div>
            <?php elseif (!$has_key): ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠️ Noch kein API-Key hinterlegt!</strong><br>
                    Dieses Plugin benötigt einen API-Key vom zentralen Dashboard, um zu funktionieren.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success inline">
                    <p><strong>✅ API-Key ist konfiguriert</strong><br>
                    Diese WordPress-Installation ist mit dem Dashboard verbunden.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpbc_save_key', 'wpbc_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bridge_key">API-Key</label>
                        </th>
                        <td>
                            <input name="bridge_key" 
                                   type="text" 
                                   id="bridge_key" 
                                   value="<?php echo esc_attr($current_key); ?>" 
                                   class="regular-text"
                                   placeholder="Gib hier den API-Key vom Dashboard ein"
                                   <?php echo $is_placeholder ? 'style="border-color: #f0b849; background: #fffbf0;"' : ''; ?>>
                            <p class="description">
                                Trage hier den API-Key ein, den du von deinem zentralen WP Maintenance Monitor Dashboard erhalten hast.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="bridge_save_key" 
                           class="button button-primary" 
                           value="API-Key speichern">
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📋 Schnellstart-Anleitung</h2>
            <ol style="line-height: 2;">
                <li>Kopiere den <strong>API-Key</strong> aus deinem WP Maintenance Monitor Dashboard</li>
                <li>Füge ihn oben in das Feld ein</li>
                <li>Klicke auf <strong>"API-Key speichern"</strong></li>
                <li>Fertig! Deine Seite ist jetzt zentral verwaltbar</li>
            </ol>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>🔒 Sicherheit</h2>
            <p>Dieses Plugin:</p>
            <ul>
                <li>✅ Erfordert einen gültigen API-Key für alle Anfragen</li>
                <li>✅ Nutzt WordPress eigene Update-Mechanismen</li>
                <li>✅ Führt nur Updates durch, keine anderen Aktionen</li>
                <li>✅ Loggt keine sensiblen Daten</li>
            </ul>
            <p style="padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; margin-top: 15px;">
                <strong>⚠️ Wichtig:</strong> Teile deinen API-Key niemals öffentlich. 
                Jeder mit diesem Key kann Updates auf dieser WordPress-Installation ausführen.
            </p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>ℹ️ Plugin-Information</h2>
            <p><strong>Version:</strong> 3.0.0</p>
            <p><strong>Status:</strong> 
                <?php if ($has_key): ?>
                    <span style="color: #46b450; font-weight: bold;">● Aktiv & Verbunden</span>
                <?php else: ?>
                    <span style="color: #dc3232; font-weight: bold;">● Wartet auf Konfiguration</span>
                <?php endif; ?>
            </p>
            <p><strong>API-Endpunkt:</strong> <code><?php echo esc_html(get_rest_url(null, 'bridge/v1')); ?></code></p>
        </div>
    </div>
    
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
        }
        .card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .notice.inline {
            margin: 15px 0;
        }
    </style>
    <?php
}

/**
 * REST API Endpunkte registrieren
 */
add_action('rest_api_init', function () {
    
    // Authentifizierungs-Callback (wiederverwendbar)
    $auth_callback = function($request) {
        $stored_key = get_option('bridge_api_key', '');
        $request_key = $request->get_header('X-Bridge-Key');
        
        // Platzhalter nicht akzeptieren
        if ($stored_key === '{{API_KEY}}') {
            return false;
        }
        
        // Key muss gesetzt sein und übereinstimmen
        if (empty($stored_key) || empty($request_key)) {
            return false;
        }
        
        return hash_equals($stored_key, $request_key);
    };

    /**
     * ENDPUNKT 1: Status abfragen
     * GET /wp-json/bridge/v1/status
     */
    register_rest_route('bridge/v1', '/status', [
        'methods' => 'GET',
        'callback' => 'wpbc_get_status',
        'permission_callback' => $auth_callback,
    ]);

    /**
     * ENDPUNKT 2: Plugin aktualisieren
     * POST /wp-json/bridge/v1/update-plugin
     */
    register_rest_route('bridge/v1', '/update-plugin', [
        'methods' => 'POST',
        'callback' => 'wpbc_update_plugin',
        'permission_callback' => $auth_callback,
        'args' => [
            'slug' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);

    /**
     * ENDPUNKT 3: Theme aktualisieren
     * POST /wp-json/bridge/v1/update-theme
     */
    register_rest_route('bridge/v1', '/update-theme', [
        'methods' => 'POST',
        'callback' => 'wpbc_update_theme',
        'permission_callback' => $auth_callback,
        'args' => [
            'slug' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);
    
    /**
     * ENDPUNKT 4: WordPress Core aktualisieren
     * POST /wp-json/bridge/v1/update-core
     */
    register_rest_route('bridge/v1', '/update-core', [
        'methods' => 'POST',
        'callback' => 'wpbc_update_core',
        'permission_callback' => $auth_callback,
    ]);
});

/**
 * Status abrufen (Version, Updates)
 */
function wpbc_get_status() {
    // Update-Funktionen laden
    if (!function_exists('get_plugin_updates')) {
        require_once(ABSPATH . 'wp-admin/includes/update.php');
    }
    if (!function_exists('wp_get_update_data')) {
        require_once(ABSPATH . 'wp-admin/includes/update.php');
    }
    
    // Plugin-Updates abrufen
    $plugin_updates = get_plugin_updates();
    $plugin_slugs = [];
    foreach ($plugin_updates as $file => $data) {
        $plugin_slugs[] = $file;
    }
    
    // Theme-Updates abrufen
    $theme_updates = get_theme_updates();
    $theme_slugs = [];
    foreach ($theme_updates as $slug => $data) {
        $theme_slugs[] = $slug;
    }
    
    // Core-Updates prüfen
    $core_updates = get_core_updates();
    $core_available = false;
    
    if (!empty($core_updates) && isset($core_updates[0])) {
        if ($core_updates[0]->response === 'upgrade') {
            $core_available = true;
        }
    }
    
    return new WP_REST_Response([
        'version' => get_bloginfo('version'),
        'site_name' => get_bloginfo('name'),
        'updates' => [
            'counts' => [
                'plugins' => count($plugin_slugs),
                'themes' => count($theme_slugs),
                'core' => $core_available ? 1 : 0,
            ],
            'plugin_names' => $plugin_slugs,
            'theme_names' => $theme_slugs,
            'core_available' => $core_available,
        ],
        'timestamp' => current_time('mysql'),
    ], 200);
}

/**
 * Plugin aktualisieren
 */
function wpbc_update_plugin($request) {
    $slug = $request->get_param('slug');
    
    // Benötigte Dateien laden
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/misc.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    
    // Filesystem initialisieren
    if (!function_exists('request_filesystem_credentials')) {
        require_once(ABSPATH . 'wp-admin/includes/template.php');
    }
    
    // Versuche direkten Zugriff
    $url = wp_nonce_url('update.php?action=upgrade-plugin&plugin=' . urlencode($slug), 'upgrade-plugin_' . $slug);
    
    if (false === ($credentials = request_filesystem_credentials($url, '', false, false, null))) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Filesystem credentials required',
            'slug' => $slug,
        ], 500);
    }

    if (!WP_Filesystem($credentials)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Could not initialize filesystem',
            'slug' => $slug,
        ], 500);
    }

    // Update durchführen
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);
    $result = $upgrader->upgrade($slug);
    
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $result->get_error_message(),
            'slug' => $slug,
        ], 500);
    }
    
    return new WP_REST_Response([
        'success' => (bool) $result,
        'slug' => $slug,
        'message' => 'Plugin successfully updated',
    ], 200);
}

/**
 * Theme aktualisieren
 */
function wpbc_update_theme($request) {
    $slug = $request->get_param('slug');
    
    // Benötigte Dateien laden
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/misc.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
    require_once(ABSPATH . 'wp-admin/includes/theme.php');
    
    // Filesystem initialisieren
    if (!function_exists('request_filesystem_credentials')) {
        require_once(ABSPATH . 'wp-admin/includes/template.php');
    }
    
    $url = wp_nonce_url('update.php?action=upgrade-theme&theme=' . urlencode($slug), 'upgrade-theme_' . $slug);
    
    if (false === ($credentials = request_filesystem_credentials($url, '', false, false, null))) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Filesystem credentials required',
            'slug' => $slug,
        ], 500);
    }

    if (!WP_Filesystem($credentials)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Could not initialize filesystem',
            'slug' => $slug,
        ], 500);
    }

    // Update durchführen
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Theme_Upgrader($skin);
    $result = $upgrader->upgrade($slug);
    
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $result->get_error_message(),
            'slug' => $slug,
        ], 500);
    }
    
    return new WP_REST_Response([
        'success' => (bool) $result,
        'slug' => $slug,
        'message' => 'Theme successfully updated',
    ], 200);
}

/**
 * WordPress Core aktualisieren
 */
function wpbc_update_core($request) {
    // Benötigte Dateien laden
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
    require_once(ABSPATH . 'wp-admin/includes/update.php');
    require_once(ABSPATH . 'wp-admin/includes/misc.php');

    // Verfügbare Updates prüfen
    $updates = get_core_updates();
    
    if (!isset($updates[0]) || $updates[0]->response !== 'upgrade') {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'No core update available',
            'current_version' => get_bloginfo('version'),
        ], 400);
    }
    
    $update = $updates[0];
    
    // Filesystem initialisieren
    if (!function_exists('request_filesystem_credentials')) {
        require_once(ABSPATH . 'wp-admin/includes/template.php');
    }
    
    $url = wp_nonce_url('update-core.php?action=do-core-upgrade', 'upgrade-core');
    
    if (false === ($credentials = request_filesystem_credentials($url, '', false, ABSPATH, null))) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Filesystem credentials required',
        ], 500);
    }

    if (!WP_Filesystem($credentials)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Could not initialize filesystem',
        ], 500);
    }

    // Core Update durchführen
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Core_Upgrader($skin);
    $result = $upgrader->upgrade($update);
    
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'error' => $result->get_error_message(),
        ], 500);
    }
    
    return new WP_REST_Response([
        'success' => !is_wp_error($result),
        'message' => 'WordPress Core successfully updated',
        'new_version' => get_bloginfo('version'),
    ], 200);
}

/**
 * Admin-Hinweis anzeigen, wenn noch kein API-Key gesetzt ist
 */
add_action('admin_notices', function() {
    $api_key = get_option('bridge_api_key', '{{API_KEY}}');
    
    if (empty($api_key) || $api_key === '{{API_KEY}}') {
        $settings_url = admin_url('options-general.php?page=wp-bridge-connector');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>WP Bridge Connector:</strong> 
                Noch kein API-Key konfiguriert. 
                <a href="<?php echo esc_url($settings_url); ?>">Jetzt einrichten</a>
            </p>
        </div>
        <?php
    }
});
