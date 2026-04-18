<?php
/**
 * Plugin Name: Staging Mail Blocker
 * Description: Bloqueia envios de e-mail reais no ambiente de staging.
 */

add_filter('pre_wp_mail', function ($pre, $atts) {
    error_log('Staging mail blocked: ' . wp_json_encode($atts));
    return true;
}, 10, 2);
