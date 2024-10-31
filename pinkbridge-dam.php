<?php
/**
 * Plugin Name:       Pinkbridge WP Tool
 * Plugin URI:        https://pinkbridge.de
 * Description:       With the Digital Asset Management System as the cornerstone for our all-in-one marketing software "PinkBridge", You have all your digital files centrally in one place.
 * Version:           1.0.0
 * Author:            PinkBridge
 * Author URI:        https://pinkbridge.de/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pinkbridge-dam
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Currently plugin version.
define( 'PINKBRIDGE_DAM_VERSION', '1.0.0' );

// Plugin directory
if ( ! defined( 'PINKBRIDGE_DAM_PLUGIN_NAME' ) ) {
	define( 'PINKBRIDGE_DAM_PLUGIN_NAME', plugin_basename(__FILE__) );
}

// Plugin directory
if ( ! defined( 'PINKBRIDGE_DAM_PLUGIN_DIR' ) ) {
	define( 'PINKBRIDGE_DAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin folder URL.
if ( ! defined( 'PINKBRIDGE_DAM_PLUGIN_URL' ) ) {
	define( 'PINKBRIDGE_DAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin root file.
if ( ! defined( 'PINKBRIDGE_DAM_PLUGIN_FILE' ) ) {
	define( 'PINKBRIDGE_DAM_PLUGIN_FILE', __FILE__ );
}

// Plugin root file.
if ( ! defined( 'PINKBRIDGE_DAM_API_NAMESPACE' ) ) {
	define( 'PINKBRIDGE_DAM_API_NAMESPACE', 'pinkbridge/v1' );
}

// Options
if ( ! defined( 'PINKBRIDGE_DAM_OPTIONS' ) ) {
	define( 'PINKBRIDGE_DAM_OPTIONS', 'pinkbridge_dam_options' );
}

// Domain Name
if ( ! defined( 'PINKBRIDGE_DOMAIN_NAME' ) ) {
	define( 'PINKBRIDGE_DOMAIN_NAME', site_url());
}

// Login API URL
if ( ! defined( 'PINKBRIDGE_LOGIN_API' ) ) {
	define( 'PINKBRIDGE_LOGIN_API', '/api/Auth/wordpress-login');
}

// Formular API URL
if ( ! defined( 'PINKBRIDGE_FORMULAR_API' ) ) {
	define( 'PINKBRIDGE_FORMULAR_API', '/api/WebForm/getFormEncryptedData');
}

// Refresh token API
if ( ! defined( 'PINKBRIDGE_REFRESH_TOKEN' ) ) {
	define( 'PINKBRIDGE_REFRESH_TOKEN', '/api/Auth/wordpress-refresh-token');
}

// Formular API URL
if ( ! defined( 'PINKBRIDGE_DAM_MEDIA_API' ) ) {
	define( 'PINKBRIDGE_DAM_MEDIA_API', '/api/Common/GetTreeDataWithFilter');
}

// Send Form data API URL
if ( ! defined( 'PINKBRIDGE_SEND_FORM_DATA' ) ) {
	define( 'PINKBRIDGE_SEND_FORM_DATA', '/api/Website/updateWordpressWebsitePageFormId');
}

// Image display API URL
if ( ! defined( 'PINKBRIDGE_IMAGE_DISPLAY_API' ) ) {
	define( 'PINKBRIDGE_IMAGE_DISPLAY_API', '/api/Common/WordpressDownloadFile');
}

// file download API URL
if ( ! defined( 'PINKBRIDGE_FILE_DOWNLOAD_API' ) ) {
	define( 'PINKBRIDGE_FILE_DOWNLOAD_API', '/api/Common/DownloadFile');
}

/**
 * Include required files
 */
require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-activator.php';
require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-deactivator.php';
require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam.php';
require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-shortcodes.php';
require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-ajax.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pinkbridge-dam-activator.php
 */
function pinkbridge_activate_pinkbridge_dam() {
	Pinkbridge_Dam_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pinkbridge-dam-deactivator.php
 */
function pinkbridge_deactivate_pinkbridge_dam() {
	Pinkbridge_Dam_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'pinkbridge_activate_pinkbridge_dam' );
register_deactivation_hook( __FILE__, 'pinkbridge_deactivate_pinkbridge_dam' );

/**
 * Function to add setting links on plugin directory
 */
add_filter( 'plugin_action_links_' . PINKBRIDGE_DAM_PLUGIN_NAME, 'pinkbridge_ptc_dam_add_action_links' );
function pinkbridge_ptc_dam_add_action_links ( $actions ) {
	$admin_page_url = menu_page_url('pinkbridge-formular', false);
	$link_text = esc_html__('Settings', 'pinkbridge-dam');
	$link = sprintf(
		'<a href="%s">%s</a>',
		esc_url($admin_page_url),
		$link_text
	);
    $actions[] = wp_kses_post($link);
	return $actions;
}

/**
 * Hook to enqueue scripts and styles
 */
add_action('wp_enqueue_scripts', 'pinkbridge_plugin_enqueue_scripts');
function pinkbridge_plugin_enqueue_scripts() {
    wp_enqueue_script('jquery');
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function pinkbridge_run_pinkbridge_dam() {
	$plugin = new Pinkbridge_Dam();
	$plugin->run();

}
pinkbridge_run_pinkbridge_dam();