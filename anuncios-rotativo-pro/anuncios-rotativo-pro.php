<?php
/**
 * Plugin Name:       Anuncios Rotativo Pro
 * Description:       Gestiona banners de anuncios rotativos con CPT, shortcodes, fechas de vencimiento, dimensiones y soporte para AdSense.
 * Version:           1.7.3
 * Author:            PG Multimedias
 * Author URI:        https://pgmultimedias.com
 * License:           GPL-2.0+
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       anuncios-rotativo-pro
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ARP_PLUGIN_FILE', __FILE__);
define('ARP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ARP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARP_VERSION', '1.7.3');

$arp_modules = array(
    'includes/core/post-type.php',
    'includes/core/helpers.php',
    'includes/admin/columns.php',
    'includes/admin/meta-boxes.php',
    'includes/admin/settings.php',
    'includes/frontend/shortcodes.php',
    'includes/frontend/display-modes.php',
    'includes/frontend/assets.php',
    'includes/cron.php',
);

foreach ($arp_modules as $arp_module) {
    require_once ARP_PLUGIN_DIR . $arp_module;
}

unset($arp_modules, $arp_module);
