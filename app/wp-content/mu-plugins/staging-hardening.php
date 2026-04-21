<?php
/**
 * Plugin Name: Staging Hardening
 * Description: Restringe pontos de exposição desnecessários no ambiente de staging.
 */

// ACESSO TEMPORÁRIO LIBERADO — restaurar após revisão
// add_action('init', function () {
//     if ((int) get_option('blog_public') !== 0) {
//         update_option('blog_public', 0);
//     }
// }, 1);

add_action('plugins_loaded', function () {
    if (!defined('XMLRPC_REQUEST') || XMLRPC_REQUEST !== true) {
        return;
    }

    status_header(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'XML-RPC disabled on staging.';
    exit;
}, 0);

add_filter('xmlrpc_enabled', '__return_false');

add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in()) {
        return $endpoints;
    }

    unset($endpoints['/wp/v2/users']);
    unset($endpoints['/wp/v2/users/(?P<id>[\\d]+)']);

    return $endpoints;
});

add_filter('robots_txt', function () {
    return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: https://staging.mmarchonimoveis.com.br/wp-sitemap.xml";
}, 999);

add_filter('wp_is_application_passwords_available', '__return_false');

// ACESSO TEMPORÁRIO LIBERADO — restaurar após revisão
// add_filter('wp_headers', function ($headers) {
//     $headers['X-Robots-Tag'] = 'noindex, nofollow, noarchive';
//     return $headers;
// });

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="notice notice-warning"><p><strong>Ambiente STAGING.</strong> Indexacao bloqueada e envio de e-mails externos desativado.</p></div>';
});
