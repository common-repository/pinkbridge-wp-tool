<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PinkBridge_DAM_API' ) ) {
    require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-api.php';
}

$result = array();
$ptc_api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
$ptc_access_token = pinkbridge_dam_get_options('ptc_access_token');
$ptc_refresh_token = pinkbridge_dam_get_options('ptc_refresh_token');
$token_status = pinkbridge_dam_is_access_token_valid();
$re_module = false;
$get_module = '';

if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field ( wp_unslash ($_GET['_wpnonce'])), 'ptc-dam-module') && $_GET) {
    if($_GET && isset($_GET['module']) && !empty($_GET['module']) && (sanitize_text_field($_GET['module']) == 'api-endpoint' || sanitize_text_field($_GET['module']) == 'refresh-authentication')){
        $re_module = true;
        $get_module = sanitize_text_field($_GET['module']);
    }
} else {
    if($_GET && isset($_GET['module']) && !empty($_GET['module']) && (sanitize_text_field($_GET['module']) == 'api-endpoint' || sanitize_text_field($_GET['module']) == 'refresh-authentication')){
        wp_die('Invalid nonce');
    }
} ?>

<div class="pinkbridge-outer-wrapper">
    <?php if(empty($ptc_api_endpoint) || ($re_module == true && $get_module == 'api-endpoint') ){
        if ( is_file( PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-api-endpoint.php' ) ) {
            include_once PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-api-endpoint.php';
        }
    } elseif(empty($ptc_access_token) || empty($ptc_refresh_token) || $token_status == false || ($re_module == true && $get_module == 'refresh-authentication')){
        if ( is_file( PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-login.php' ) ) {
            include_once PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-login.php';
        }
    } elseif(!empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true){
        if ( is_file( PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-formular.php' ) ) {
            include_once PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-formular.php';
        }  
    } ?>
</div>