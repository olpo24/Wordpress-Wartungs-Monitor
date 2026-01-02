<?php
/**
 * Plugin Name: WP Maintenance Monitor
 * Description: Zentrales Dashboard zur Verwaltung von Remote-Updates und One-Click Admin Login.
 * Version: 3.0.2
 * Author: Dein Name
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_Maintenance_Monitor')) {

    class WP_Maintenance_Monitor {
        private $table_sites;
        private $table_logs;
        private $version = '3.0.2';

        public function __construct() {
            global $wpdb;
            $this->table_sites = $wpdb->prefix . 'wpmm_sites';
            $this->table_logs = $wpdb->prefix . 'wpmm_logs';

            register_activation_hook(__FILE__, array($this, 'activate'));
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('admin_init', array($this, 'handle_bridge_download'));

            $actions = ['get_status', 'update_plugin', 'update_theme', 'update_core', 'add_site', 'update_site', 'delete_site', 'get_login_url'];
            foreach ($actions as $action) {
                add_action("wp_ajax_wpmm_$action", array($this, "ajax_$action"));
            }
        }

        public function activate() {
            global $wpdb;
            $charset = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$this->table_sites} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                url varchar(255) NOT NULL,
                api_key varchar(64) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset;
            CREATE TABLE {$this->table_logs} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                site_id mediumint(9) NOT NULL,
                action varchar(50) NOT NULL,
                details text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        public function add_admin_menu() {
            add_menu_page('Maintenance', 'Maintenance', 'manage_options', 'wp-maintenance-monitor', array($this, 'render_dashboard'), 'dashicons-admin-generic');
            add_submenu_page('wp-maintenance-monitor', 'Dashboard', 'Dashboard', 'manage_options', 'wp-maintenance-monitor', array($this, 'render_dashboard'));
            add_submenu_page('wp-maintenance-monitor', 'Einstellungen', 'Einstellungen', 'manage_options', 'wp-maintenance-monitor-settings', array($this, 'render_settings'));
        }

        public function enqueue_assets($hook) {
            if (strpos($hook, 'wp-maintenance-monitor') === false) return;
            wp_enqueue_style('wpmm-styles', plugin_dir_url(__FILE__) . 'assets/styles.css', array(), $this->version);
            wp_enqueue_script('wpmm-js', plugin_dir_url(__FILE__) . 'assets/dashboard.js', array('jquery'), $this->version, true);
            wp_localize_script('wpmm-js', 'wpmmData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpmm_nonce')
            ));
        }

        public function handle_bridge_download() {
    if (isset($_GET['action']) && $_GET['action'] === 'download_bridge' && isset($_GET['api_key'])) {
        if (!current_user_can('manage_options')) return;

        $api_key = sanitize_text_field($_GET['api_key']);
        $template_path = plugin_dir_path(__FILE__) . 'bridge-connector-template.php';

        if (!file_exists($template_path)) {
            wp_die('Template-Datei bridge-connector-template.php nicht gefunden!');
        }

        // Template laden und Platzhalter ersetzen
        $content = file_get_contents($template_path);
        $content = str_replace('YOUR_API_KEY_HERE', $api_key, $content);
        $content = str_replace('YOUR_DASHBOARD_URL_HERE', get_site_url(), $content);

        // ZIP erstellen
        $zip = new ZipArchive();
        $zip_name = 'wp-bridge-connector.zip';
        $temp_file = wp_tempnam($zip_name);

        if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Die Datei innerhalb der ZIP heißt wp-bridge-connector.php
            $zip->addFromString('wp-bridge-connector.php', $content);
            $zip->close();

            // Header für ZIP-Download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($temp_file));
            header('Pragma: no-cache');
            header('Expires: 0');

            readfile($temp_file);
            unlink($temp_file); // Temp-Datei löschen
            exit;
        } else {
            wp_die('Fehler beim Erstellen der ZIP-Datei. Stellen Sie sicher, dass die PHP ZipArchive-Erweiterung installiert ist.');
        }
    }
}

        public function ajax_add_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $name = sanitize_text_field($_POST['name']);
            $url  = esc_url_raw($_POST['url']);
            $api_key = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(time().rand());
            global $wpdb;
            $wpdb->insert($this->table_sites, ['name' => $name, 'url' => $url, 'api_key' => $api_key]);
            wp_send_json_success(['api_key' => $api_key]);
        }

        public function ajax_get_login_url() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $site = $this->get_site(intval($_POST['id']));
            $response = $this->api_request($site->url, '/get-login-url', $site->api_key);
            if (isset($response['success']) && $response['success']) {
                wp_send_json_success(['login_url' => $response['login_url']]);
            } else {
                wp_send_json_error(['message' => 'Bridge Error']);
            }
        }

        public function ajax_get_status() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $site = $this->get_site(intval($_POST['id']));
            if (!$site) wp_send_json_error();
            wp_send_json_success($this->api_request($site->url, '/status', $site->api_key));
        }

        public function ajax_update_plugin() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $site = $this->get_site(intval($_POST['id']));
            wp_send_json_success($this->api_request($site->url, '/update-plugin', $site->api_key, ['plugin' => $_POST['item']]));
        }

        public function ajax_update_theme() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $site = $this->get_site(intval($_POST['id']));
            wp_send_json_success($this->api_request($site->url, '/update-theme', $site->api_key, ['theme' => $_POST['item']]));
        }

        public function ajax_update_core() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $site = $this->get_site(intval($_POST['id']));
            wp_send_json_success($this->api_request($site->url, '/update-core', $site->api_key, []));
        }

        public function ajax_update_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            global $wpdb;
            $wpdb->update($this->table_sites, ['name' => $_POST['name'], 'url' => $_POST['url']], ['id' => intval($_POST['id'])]);
            wp_send_json_success();
        }

        public function ajax_delete_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            global $wpdb;
            $wpdb->delete($this->table_sites, ['id' => intval($_POST['id'])]);
            wp_send_json_success();
        }

        private function get_site($id) {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_sites} WHERE id = %d", $id));
        }

        private function api_request($url, $endpoint, $api_key, $post_data = null) {
            $full_url = rtrim($url, '/') . '/wp-json/bridge/v1' . $endpoint;
            $args = [
                'headers' => ['X-Bridge-Key' => $api_key, 'Content-Type' => 'application/json'],
                'timeout' => 45, 'sslverify' => false
            ];
            if ($post_data !== null) {
                $args['body'] = json_encode($post_data);
                $response = wp_remote_post($full_url, $args);
            } else {
                $response = wp_remote_get($full_url, $args);
            }
            if (is_wp_error($response)) return ['error' => $response->get_error_message()];
            return json_decode(wp_remote_retrieve_body($response), true);
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
}
