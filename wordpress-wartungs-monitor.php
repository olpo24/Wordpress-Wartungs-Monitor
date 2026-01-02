<?php
/**
 * Plugin Name: WP Maintenance Monitor
 * Description: Zentrales Dashboard zur Verwaltung von Remote-Updates und SSO Login.
 * Version: 3.1.2
 * Author: Dein Name
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_Maintenance_Monitor')) {

    class WP_Maintenance_Monitor {
        private $table_sites;
        private $version = '3.1.2';

        public function __construct() {
            global $wpdb;
            $this->table_sites = $wpdb->prefix . 'wpmm_sites';

            register_activation_hook(__FILE__, array($this, 'activate'));
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('admin_init', array($this, 'handle_bridge_download'));

            $actions = ['get_status', 'execute_update', 'add_site', 'update_site', 'delete_site', 'get_login_url'];
            foreach ($actions as $action) {
                add_action("wp_ajax_wpmm_$action", array($this, "ajax_$action"));
            }
        }

        public function activate() {
            global $wpdb;
            $charset = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table_sites} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                url varchar(255) NOT NULL,
                api_key varchar(64) NOT NULL,
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

        // --- DIESE FUNKTION VERURSACHTE OFT DEN WEISSEN BILDSCHIRM ---
        public function handle_bridge_download() {
            if (isset($_GET['action']) && $_GET['action'] === 'download_bridge' && isset($_GET['api_key'])) {
                if (!current_user_can('manage_options')) return;

                $api_key = sanitize_text_field($_GET['api_key']);
                $template_path = plugin_dir_path(__FILE__) . 'bridge-connector-template.php';
                
                if (!file_exists($template_path)) {
                    wp_die('Fehler: Die Datei bridge-connector-template.php wurde im Plugin-Ordner nicht gefunden.');
                }

                $content = file_get_contents($template_path);
                $content = str_replace('YOUR_API_KEY_HERE', $api_key, $content);

                // Prüfen ob ZIP verfügbar ist, sonst Fallback auf reine PHP Datei
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    $temp_file = wp_tempnam('bridge.zip');
                    
                    if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                        $zip->addFromString('wp-bridge-connector.php', $content);
                        $zip->close();
                        
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="wp-bridge-connector.zip"');
                        header('Content-Length: ' . filesize($temp_file));
                        readfile($temp_file);
                        unlink($temp_file);
                        exit;
                    }
                } else {
                    // Fallback: Direkter PHP Download, falls ZipArchive fehlt
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="wp-bridge-connector.php"');
                    echo $content;
                    exit;
                }
            }
        }

        // --- AJAX HANDLER ---

        public function ajax_add_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $url  = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
            
            if (empty($name) || empty($url)) {
                wp_send_json_error(['message' => 'Bitte alle Felder ausfüllen.']);
            }

            $api_key = bin2hex(openssl_random_pseudo_bytes(16));
            global $wpdb;
            $inserted = $wpdb->insert($this->table_sites, [
                'name' => $name,
                'url' => $url,
                'api_key' => $api_key
            ]);

            if ($inserted) {
                wp_send_json_success(['api_key' => $api_key]);
            } else {
                wp_send_json_error(['message' => 'Datenbankfehler beim Speichern.']);
            }
        }

        public function ajax_get_status() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $site = $this->get_site($id);
            if (!$site) wp_send_json_error(['error' => 'Seite nicht gefunden']);
            wp_send_json_success($this->api_request($site->url, '/status', $site->api_key));
        }

        public function ajax_execute_update() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $site = $this->get_site($id);
            if (!$site) wp_send_json_error();

            $result = $this->api_request($site->url, '/update', $site->api_key, [
                'type' => sanitize_text_field($_POST['update_type']),
                'slug' => sanitize_text_field($_POST['slug'] ?? '')
            ]);
            wp_send_json_success($result);
        }

        public function ajax_update_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            global $wpdb;
            $wpdb->update($this->table_sites, 
                ['name' => sanitize_text_field($_POST['name']), 'url' => esc_url_raw($_POST['url'])], 
                ['id' => $id]
            );
            wp_send_json_success();
        }

        public function ajax_delete_site() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            global $wpdb;
            $wpdb->delete($this->table_sites, ['id' => $id]);
            wp_send_json_success();
        }

        public function ajax_get_login_url() {
            check_ajax_referer('wpmm_nonce', 'nonce');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $site = $this->get_site($id);
            if (!$site) wp_send_json_error();
            wp_send_json_success($this->api_request($site->url, '/get-login-url', $site->api_key));
        }

        private function get_site($id) {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_sites} WHERE id = %d", $id));
        }

        private function api_request($url, $endpoint, $api_key, $post_data = null) {
            $full_url = rtrim($url, '/') . '/wp-json/bridge/v1' . $endpoint;
            $args = [
                'headers' => ['X-Bridge-Key' => $api_key, 'Content-Type' => 'application/json'],
                'timeout' => 60,
                'sslverify' => false
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
