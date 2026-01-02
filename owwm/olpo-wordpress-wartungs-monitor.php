<?php
/**
 * Plugin Name: Olpo-Wordpress-Wartungs-Monitor
 * Description: Zentrales Dashboard zur Verwaltung von Remote-Updates und SSO Login.
 * Version: 0.1
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
    'owwm'
);
if (!defined('ABSPATH')) exit;

class WP_Maintenance_Monitor {
    private $table_sites;

    public function __construct() {
        global $wpdb;
        $this->table_sites = $wpdb->prefix . 'wpmm_sites';
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_bridge_download'));

        // AJAX Actions
        $actions = ['get_status', 'execute_update', 'add_site', 'update_site', 'delete_site', 'get_login_url'];
        foreach ($actions as $action) {
            add_action("wp_ajax_wpmm_$action", array($this, "ajax_$action"));
        }
    }

    public function activate() {
        global $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_sites} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            url varchar(255) NOT NULL,
            api_key varchar(64) NOT NULL,
            PRIMARY KEY  (id)
        ) {$wpdb->get_charset_collate()};";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        // Hauptmenüpunkt
        add_menu_page('Maintenance', 'Maintenance', 'manage_options', 'wp-maintenance-monitor', array($this, 'render_dashboard'), 'dashicons-admin-generic');
        
        // Dashboard Unterpunkt (identisch mit Hauptmenü)
        add_submenu_page('wp-maintenance-monitor', 'Dashboard', 'Dashboard', 'manage_options', 'wp-maintenance-monitor', array($this, 'render_dashboard'));
        
        // EINSTELLUNGEN Unterpunkt (Hier war der Fehler)
        add_submenu_page('wp-maintenance-monitor', 'Einstellungen', 'Einstellungen', 'manage_options', 'wp-maintenance-monitor-settings', array($this, 'render_settings'));
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'wp-maintenance-monitor') === false) return;
        wp_enqueue_style('wpmm-style', plugin_dir_url(__FILE__) . 'assets/styles.css');
        wp_enqueue_script('wpmm-js', plugin_dir_url(__FILE__) . 'assets/dashboard.js', array('jquery'), '3.2.1', true);
        wp_localize_script('wpmm-js', 'wpmmData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpmm_nonce')
        ));
    }

    // AJAX: Neue Seite hinzufügen
    public function ajax_add_site() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        $api_key = bin2hex(random_bytes(16));
        global $wpdb;
        $wpdb->insert($this->table_sites, [
            'name' => sanitize_text_field($_POST['name']),
            'url' => esc_url_raw($_POST['url']),
            'api_key' => $api_key
        ]);
        wp_send_json_success(['api_key' => $api_key]);
    }

    // AJAX: Seite bearbeiten
    public function ajax_update_site() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        global $wpdb;
        $wpdb->update($this->table_sites, 
            ['name' => sanitize_text_field($_POST['name']), 'url' => esc_url_raw($_POST['url'])], 
            ['id' => intval($_POST['id'])]
        );
        wp_send_json_success();
    }

    // AJAX: Seite löschen
    public function ajax_delete_site() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        global $wpdb;
        $wpdb->delete($this->table_sites, ['id' => intval($_POST['id'])]);
        wp_send_json_success();
    }

    // AJAX: Status von Bridge abrufen
    public function ajax_get_status() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        $site = $this->get_site(intval($_POST['id']));
        if (!$site) wp_send_json_error();
        wp_send_json_success($this->api_request($site->url, '/status', $site->api_key));
    }

    // AJAX: Update ausführen
    public function ajax_execute_update() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        $site = $this->get_site(intval($_POST['id']));
        $res = $this->api_request($site->url, '/update', $site->api_key, [
            'type' => sanitize_text_field($_POST['update_type']),
            'slug' => sanitize_text_field($_POST['slug'])
        ]);
        wp_send_json_success($res);
    }

    // AJAX: SSO Login URL generieren
    public function ajax_get_login_url() {
        check_ajax_referer('wpmm_nonce', 'nonce');
        $site = $this->get_site(intval($_POST['id']));
        wp_send_json_success($this->api_request($site->url, '/get-login-url', $site->api_key));
    }

    private function get_site($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_sites} WHERE id = %d", $id));
    }

    private function api_request($url, $endpoint, $api_key, $data = null) {
        $args = [
            'headers' => ['X-Bridge-Key' => $api_key, 'Content-Type' => 'application/json'],
            'timeout' => 60,
            'sslverify' => false
        ];
        $target = rtrim($url, '/') . '/wp-json/bridge/v1' . $endpoint;
        
        if ($data) {
            $res = wp_remote_post($target, array_merge($args, ['body' => json_encode($data)]));
        } else {
            $res = wp_remote_get($target, $args);
        }
        
        if (is_wp_error($res)) return ['error' => $res->get_error_message()];
        return json_decode(wp_remote_retrieve_body($res), true);
    }

    public function handle_bridge_download() {
        if (isset($_GET['action']) && $_GET['action'] === 'download_bridge' && current_user_can('manage_options')) {
            $api_key = sanitize_text_field($_GET['api_key']);
            $template_file = plugin_dir_path(__FILE__) . 'bridge-connector-template.php';
            
            if (!file_exists($template_file)) wp_die('Template-Datei fehlt!');

            $template = file_get_contents($template_file);
            $content = str_replace('YOUR_API_KEY_HERE', $api_key, $template);
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="wp-bridge-connector.php"');
            echo $content; 
            exit;
        }
    }

    public function render_dashboard() {
        global $wpdb;
        $sites = $wpdb->get_results("SELECT * FROM {$this->table_sites} ORDER BY name ASC");
        include plugin_dir_path(__FILE__) . 'templates/dashboard.php';
    }

    public function render_settings() {
        include plugin_dir_path(__FILE__) . 'templates/settings.php';
    }
}
new WP_Maintenance_Monitor();
