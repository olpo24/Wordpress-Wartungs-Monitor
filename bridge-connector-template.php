<?php
/*
Plugin Name: WP Bridge Connector
Description: Connector für WP Maintenance Monitor (Legacy Support 6.1.x).
Version: 1.0.5
*/

if (!defined('ABSPATH')) exit;

$api_key = 'YOUR_API_KEY_HERE';

add_action('rest_api_init', function () {
    register_rest_route('bridge/v1', '/status', [
        'methods' => 'GET',
        'callback' => 'wpbc_get_status',
        'permission_callback' => 'wpbc_check'
    ]);
register_rest_route('bridge/v1', '/update', [
    'methods' => 'POST',
    'callback' => 'wpbc_do_update',
    'permission_callback' => 'wpbc_check'
]);

function wpbc_do_update($request) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';
    
    $params = $request->get_json_params();
    $type = $params['type']; // plugin, theme oder core
    $slug = $params['slug'] ?? ''; // z.B. 'contact-form-7/contact-form-7.php'

    // Skin für lautlose Updates (kein HTML-Output)
    $skin = new Automatic_Upgrader_Skin();

    if ($type === 'plugin') {
        $upgrader = new Plugin_Upgrader($skin);
        $result = $upgrader->upgrade($slug);
        return ['success' => $result];
    }

    if ($type === 'theme') {
        $upgrader = new Theme_Upgrader($skin);
        $result = $upgrader->upgrade($slug);
        return ['success' => $result];
    }

    if ($type === 'core') {
        $upgrader = new Core_Upgrader($skin);
        $updates = get_core_updates();
        $result = $upgrader->upgrade($updates[0]);
        return ['success' => $result];
    }

    return ['success' => false, 'error' => 'Ungültiger Typ'];
}
    register_rest_route('bridge/v1', '/get-login-url', [
        'methods' => 'GET',
        'callback' => 'wpbc_get_login',
        'permission_callback' => 'wpbc_check'
    ]);
});

function wpbc_get_status() {
    // 1. Dateien für Update-Funktionen laden
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';

    // 2. WordPress zwingen, den Update-Status neu zu berechnen
    // Das ist bei älteren Versionen wie 6.1 oft nötig, damit der Cache gefüllt ist
    if ( ! function_exists( 'get_site_transient' ) ) {
        require_once ABSPATH . 'wp-includes/option.php';
    }

    $current_plugins = get_site_transient( 'update_plugins' );
    $current_themes = get_site_transient( 'update_themes' );

    // Falls die Transients leer sind, stoßen wir einen Check an
    if ( ! $current_plugins ) {
        wp_update_plugins();
        $current_plugins = get_site_transient( 'update_plugins' );
    }

    $plugin_updates = get_plugin_updates();
    $theme_updates = get_theme_updates();
    
    // 3. Fallback-Check für WP-Core Updates
    $core_updates = get_core_updates();
    $has_core_update = false;
    if ( isset($core_updates[0]->response) && $core_updates[0]->response === 'upgrade' ) {
        $has_core_update = true;
    }

    return [
        'version' => get_bloginfo('version'),
        'updates' => [
            'counts' => [
                'plugins' => count($plugin_updates),
                'themes'  => count($theme_updates),
                'core'    => $has_core_update ? 1 : 0
            ],
            'plugin_names' => array_keys($plugin_updates),
            'theme_names'  => array_keys($theme_updates)
        ]
    ];
}

function wpbc_get_login() {
    $admins = get_users(['role' => 'administrator', 'number' => 1]);
    if (empty($admins)) return new WP_Error('no_admin', 'Kein Admin gefunden', ['status' => 404]);
    
    // SSO Token generieren
    $token = bin2hex(openssl_random_pseudo_bytes(20));
    update_option('wpbc_sso_' . $token, $admins[0]->ID, false);
    
    return [
        'success' => true, 
        'login_url' => add_query_arg('bridge_sso', $token, admin_url())
    ];
}

add_action('init', function() {
    if (isset($_GET['bridge_sso'])) {
        $token = sanitize_text_field($_GET['bridge_sso']);
        $user_id = get_option('wpbc_sso_' . $token);
        if ($user_id) {
            wp_set_auth_cookie($user_id);
            delete_option('wpbc_sso_' . $token);
            wp_redirect(admin_url());
            exit;
        }
    }
});

function wpbc_check($request) {
    global $api_key;
    $header_key = $request->get_header('X-Bridge-Key');
    return ($header_key && $header_key === $api_key);
}
