<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

$api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
$admin_page_url = menu_page_url('pinkbridge-formular', false);
$ptc_access_token = pinkbridge_dam_get_options('ptc_access_token');
$ptc_refresh_token = pinkbridge_dam_get_options('ptc_refresh_token');
$token_status = pinkbridge_dam_is_access_token_valid();
$err_msg = '';
$nonce = wp_create_nonce('ptc-dam-module');
$auth_nonce_url = add_query_arg(array(
    'module' => 'refresh-authentication',
    '_wpnonce' => $nonce,
), $admin_page_url);
if ( ! empty( $result ) && $result['status'] == 'error' ) {
    $err_msg = $result['message'];
} ?>

<div class="pinkbridge-login-outer">
    <div class="pinkbridge-login-section">
        <h1><?php esc_html_e( 'API Endpoint', 'pinkbridge-dam' ); ?></h1>
        <form  action="" method="post" class="ptc-dam-api-endpoint">
            <?php wp_nonce_field( 'pinkbridge_api_nonce_action', 'pinkbridge_api_nonce_field' ); ?>
            <div class="form-field-outer">
                <div class="form-field">
                    <label><?php esc_html_e( 'API Endpoint', 'pinkbridge-dam' ); ?></label>
                    <input type="text" name="pinkbridge_endpoint" class="pinkbridge_endpoint" id="pinkbridge_endpoint" value="<?php echo esc_url($api_endpoint); ?>"  placeholder="<?php esc_attr_e( 'https://yourapiendpoint.com/', 'pinkbridge-dam' ); ?>">
                    <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/api-icon.svg' ); ?>" alt="<?php esc_attr_e( 'API Icon', 'pinkbridge-dam' ); ?>" class="prefix-icon" />
                </div>
                <span class="error" style="display:none;"><?php esc_html_e( 'Please Enter API Endpoint', 'pinkbridge-dam' ); ?></span>
            </div>
            <input type="submit" name="ptc-api-endpoint" id="ptc_dam_save_api" value="<?php esc_attr_e( 'Save', 'pinkbridge-dam' ); ?>">
            <div class="form-loader" id="loader" style="display:none;"><?php esc_html_e( 'Loading..', 'pinkbridge-dam' ); ?></div>
        </form>
        <div class="link-wrapper">
            <?php if ( ! empty( $ptc_access_token ) && ! empty( $ptc_refresh_token ) && $token_status == true && ! empty( $api_endpoint ) ) : ?>
                <a href="<?php echo esc_url( $admin_page_url ); ?>" title="<?php esc_attr_e( 'Formular Lists', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Formular Lists', 'pinkbridge-dam' ); ?></a>
            <?php endif; ?>
            <?php if($api_endpoint){ ?>
                <a href="<?php echo esc_url( $auth_nonce_url ); ?>" title="<?php esc_attr_e( 'Authentication Screen', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Authentication Screen', 'pinkbridge-dam' ); ?></a>
            <?php } ?>
        </div>
    </div>
</div>