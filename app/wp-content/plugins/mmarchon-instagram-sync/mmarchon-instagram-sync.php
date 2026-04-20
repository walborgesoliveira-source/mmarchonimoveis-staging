<?php
/**
 * Plugin Name: MMarchon Instagram Sync
 * Plugin URI: https://staging.mmarchonimoveis.com.br
 * Description: Sincroniza posts do Instagram e os vincula aos imóveis no staging.
 * Version: 0.1.0
 * Author: Codex
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: mmarchon-instagram-sync
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MMARCHON_INSTAGRAM_SYNC_VERSION', '0.1.0');
define('MMARCHON_INSTAGRAM_SYNC_FILE', __FILE__);
define('MMARCHON_INSTAGRAM_SYNC_DIR', plugin_dir_path(__FILE__));
define('MMARCHON_INSTAGRAM_SYNC_URL', plugin_dir_url(__FILE__));
define('MMARCHON_INSTAGRAM_SYNC_CRON_HOOK', 'mmarchon_instagram_cron_event');

require_once MMARCHON_INSTAGRAM_SYNC_DIR . 'includes/class-mmarchon-instagram-sync.php';
require_once MMARCHON_INSTAGRAM_SYNC_DIR . 'admin/class-mmarchon-instagram-sync-admin.php';
require_once MMARCHON_INSTAGRAM_SYNC_DIR . 'public/class-mmarchon-instagram-sync-public.php';

register_activation_hook(MMARCHON_INSTAGRAM_SYNC_FILE, ['MMarchon_Instagram_Sync', 'activate']);
register_deactivation_hook(MMARCHON_INSTAGRAM_SYNC_FILE, ['MMarchon_Instagram_Sync', 'deactivate']);

MMarchon_Instagram_Sync::boot();
