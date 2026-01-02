<?php
/*
Plugin Name: Olpo Wordpress Wartungs Monitor Connector
* Description: Empfängt Befehle vom Olpo-WordpressWartungs-Monitor. API-Key kann in den Einstellungen hinterlegt werden.
 * Version: 0.1
 * Author: olpo
 */
// Am Anfang der Datei (nach dem Plugin Header)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Update Checker initialisieren
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/olpo24/Olpo-Wordpress-Wartungs-Monitor/',
    __FILE__,
    'owwm-child'
);
// 3. ZWINGEND: Nur Assets verwenden (Dies überschreibt den zipball-Link)
$myUpdateChecker->getStrategy()->setContext('releases'); 
// Falls 'releases' nicht reicht, versuche alternativ:
// $myUpdateChecker->getStrategy()->useReleaseAssets();

if (!defined('ABSPATH')) exit;

class WP_Bridge_Connector_Static {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('init', [$this, 'handle_sso_login']);
    }

    // 1. Einstellungsseite auf der Zielseite
    public function add_settings_menu() {
        add_options_page('Bridge Connector', 'Bridge Connector', 'manage_options', 'bridge-connector', [$this, 'render_settings_page']);
    }

    public function register_settings() {
        register_setting('wpbc_settings_group', 'wpbc_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Bridge Connector Einstellungen</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wpbc_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API-Key</th>
                        <td>
                            <input type="text" name="wpbc_api_key" value="<?php echo esc_attr(get_option('wpbc_api_key')); ?>" class="regular-text" style="font-family:monospace;">
                            <p class="description">Kopiere den Key aus deinem zentralen Wartungs-Dashboard hierher.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Key speichern'); ?>
            </form>
            <div style="margin-top:20px; padding:15px; background:#e7f4e9; border-left:4px solid #46b450;">
                <strong>Status:</strong> <?php echo get_option('wpbc_api_key') ? '✅ Key hinterlegt. Verbindung bereit.' : '❌ Kein Key gefunden.'; ?>
            </div>
        </div>
        <?php
    }

    // 2. API Endpunkte
    public function register_routes() {
        register_rest_route('bridge/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_auth']
        ]);
        register_rest_route('bridge/v1', '/update', [
            'methods' => 'POST',
            'callback' => [$this, 'do_update'],
            'permission_callback' => [$this, 'check_auth']
        ]);
        register_rest_route('bridge/v1', '/get-login-url', [
            'methods' => 'GET',
            'callback' => [$this, 'generate_sso_url'],
            'permission_callback' => [$this, 'check_auth']
        ]);
    }

    public function check_auth($request) {
        $saved_key = get_option('wpbc_api_key');
        $sent_key = $request->get_header('X-Bridge-Key');
        return (!empty($saved_key) && $sent_key === $saved_key);
    }

    public function get_status() {
        require_once ABSPATH . 'wp-admin/includes/update.php';
        wp_update_plugins();
        wp_update_themes();

        $plugins = get_plugin_updates();
        $themes = get_theme_updates();
        $core = get_core_updates();
        $has_core = (isset($core[0]->response) && $core[0]->response === 'upgrade');

        return [
            'version' => get_bloginfo('version'),
            'updates' => [
                'counts' => ['plugins' => count($plugins), 'themes' => count($themes), 'core' => $has_core ? 1 : 0],
                'plugin_names' => array_keys($plugins),
                'theme_names' => array_keys($themes)
            ]
        ];
    }

    public function do_update($request) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        $params = $request->get_json_params();
        $type = $params['type'];
        $slug = $params['slug'];
        $skin = new Automatic_Upgrader_Skin();
        
        if ($type === 'plugin') {
            $upgrader = new Plugin_Upgrader($skin);
            $result = $upgrader->upgrade($slug);
            delete_site_transient('update_plugins');
        } elseif ($type === 'theme') {
            $upgrader = new Theme_Upgrader($skin);
            $result = $upgrader->upgrade($slug);
            delete_site_transient('update_themes');
        } elseif ($type === 'core') {
            $upgrader = new Core_Upgrader($skin);
            $updates = get_core_updates();
            $result = $upgrader->upgrade($updates[0]);
        }

        return ['success' => ($result === true || is_array($result))];
    }

    public function generate_sso_url() {
        $admins = get_users(['role' => 'administrator', 'number' => 1]);
        if (empty($admins)) return ['error' => 'No admin found'];
        
        $token = bin2hex(random_bytes(20));
        update_option('wpbc_sso_' . $token, $admins[0]->ID, false);
        // Token läuft nach 60 Sekunden ab
        wp_schedule_single_event(time() + 60, 'wpbc_cleanup_sso', [$token]);

        return [
            'success' => true,
            'login_url' => add_query_arg('bridge_sso', $token, admin_url())
        ];
    }

    public function handle_sso_login() {
        if (isset($_GET['bridge_sso'])) {
            $token = sanitize_text_field($_GET['bridge_sso']);
            $user_id = get_option('wpbc_sso_' . $token);
            if ($user_id) {
                delete_option('wpbc_sso_' . $token);
                wp_set_auth_cookie($user_id);
                wp_redirect(admin_url());
                exit;
            }
        }
    }
}

new WP_Bridge_Connector_Static();
