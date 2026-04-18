<?php
/**
 * Plugin Name: Staging Hardening
 * Description: Restringe pontos de exposição desnecessários no ambiente de staging.
 */

add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in()) {
        return $endpoints;
    }

    unset($endpoints['/wp/v2/users']);
    unset($endpoints['/wp/v2/users/(?P<id>[\\d]+)']);

    return $endpoints;
});
