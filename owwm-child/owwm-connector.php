<?php
/*
Plugin Name: Olpo Wordpress Wartungs Monitor Connector
Version: 1.0.6
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
    register_rest_route('bridge/v1', '/get-login-url', [
        'methods' => 'GET',
        'callback' => 'wpbc_get_login',
        'permission_callback' => 'wpbc_check'
    ]);
});

function wpbc_get_status() {
    require_once ABSPATH . 'wp-admin/includes/update.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';

    wp_update_plugins();
    wp_update_themes();

    $plugin_updates = get_plugin_updates();
    $theme_updates = get_theme_updates();
    $core_updates = get_core_updates();
    
    $has_core_update = (isset($core_updates[0]->response) && $core_updates[0]->response === 'upgrade');

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

function wpbc_do_update($request) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';
    
    $params = $request->get_json_params();
    $type = $params['type'];
    $slug = $params['slug'] ?? '';
    $skin = new Automatic_Upgrader_Skin();
    $result = false;

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

    return ['success' => ($result === true || (is_array($result) && !empty($result)))];
}

function wpbc_get_login() {
    $admins = get_users(['role' => 'administrator', 'number' => 1]);
    if (empty($admins)) return new WP_Error('no_admin', 'Kein Admin gefunden');
    $token = bin2hex(openssl_random_pseudo_bytes(20));
    update_option('wpbc_sso_' . $token, $admins[0]->ID, false);
    return ['success' => true, 'login_url' => add_query_arg('bridge_sso', $token, admin_url())];
}

add_action('init', function() {
    if (isset($_GET['bridge_sso'])) {
        $user_id = get_option('wpbc_sso_' . $_GET['bridge_sso']);
        if ($user_id) {
            wp_set_auth_cookie($user_id);
            delete_option('wpbc_sso_' . $_GET['bridge_sso']);
            wp_redirect(admin_url());
            exit;
        }
    }
});

function wpbc_check($request) {
    global $api_key;
    return ($request->get_header('X-Bridge-Key') === $api_key);
}
