<?php
/**
 * Plugin Name: Marchon CRM
 * Description: CRM inicial para leads imobiliarios com foco em interesse por terrenos.
 * Version: 0.1.0
 * Author: Marchon
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-marchon-crm.php';

register_activation_hook(__FILE__, ['Marchon_CRM', 'ensure_front_page']);

Marchon_CRM::boot(__FILE__);
