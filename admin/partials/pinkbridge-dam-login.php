<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$admin_page_url = menu_page_url('pinkbridge-formular', false);
$api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint'); 
$ptc_access_token = pinkbridge_dam_get_options('ptc_access_token');
$ptc_refresh_token = pinkbridge_dam_get_options('ptc_refresh_token');
$token_status = pinkbridge_dam_is_access_token_valid();
$err_msg = '';
$nonce = wp_create_nonce('ptc-dam-module');
$nonce_url = add_query_arg(array(
    'module' => 'api-endpoint',
    '_wpnonce' => $nonce,
), $admin_page_url);
if ( ! empty( $result ) && isset( $result['status'] ) && $result['status'] === 'error' ) {
    $err_msg = $result['message'];
} ?>

<div class="pinkbridge-login-outer">
    <div class="pinkbridge-login-section">
        <h1><?php esc_html_e( 'Authentication', 'pinkbridge-dam' ); ?></h1>
        <form  action="" method="post" class="ptc-dam-login-form">
            <?php wp_nonce_field( 'pinkbridge_login_nonce_action', 'pinkbridge_login_nonce_field' ); ?>
            <div class="form-field-outer">
                <div class="form-field">
                    <label for="pinkbridge_endpoint"><?php esc_html_e( 'API endpoint', 'pinkbridge-dam' ); ?></label>
                    <input type="text" id="pinkbridge_endpoint" value="<?php echo esc_url( $api_endpoint ); ?>" name="pinkbridge_endpoint" class="pinkbridge_endpoint" placeholder="<?php esc_attr_e( 'API endpoint', 'pinkbridge-dam' ); ?>" readonly>
                    <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/api-icon.svg' ); ?>" alt="<?php esc_attr_e( 'API Icon', 'pinkbridge-dam' ); ?>" class="prefix-icon" />
                </div>
            </div>
            <div class="form-field-outer">
                <div class="form-field">
                    <label for="ptc-user-name"><?php esc_html_e( 'Email', 'pinkbridge-dam' ); ?></label>
                    <input type="text" value="" name="ptc-user-name" class="ptc-user-name" placeholder="<?php esc_attr_e( 'Email', 'pinkbridge-dam' ); ?>">
                    <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/user.svg' ); ?>" alt="<?php esc_attr_e( 'User Icon', 'pinkbridge-dam' ); ?>" class="prefix-icon"/>
                </div>
            </div>
            <div class="form-field-outer">
                <div class="form-field">
                    <label for="ptc-user-password"><?php esc_html_e( 'Password', 'pinkbridge-dam' ); ?></label>
                    <input type="password" id="ptc-user-password" value="" name="ptc-user-password" class="ptc-user-password has-password" placeholder="<?php esc_attr_e( 'Password', 'pinkbridge-dam' ); ?>">
                    <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/lock-icon.svg' ); ?>" alt="<?php esc_attr_e( 'Lock Icon', 'pinkbridge-dam' ); ?>" class="prefix-icon"/>
                    <span class="toggle-password">
                        <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/eye-icon.svg' ); ?>" alt="<?php esc_attr_e( 'Eye Icon', 'pinkbridge-dam' ); ?>" class="show-icon"/>
                        <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/eye-slash.svg' ); ?>" alt="<?php esc_attr_e( 'Eye Slash Icon', 'pinkbridge-dam' ); ?>" class="hide-icon"/>
                    </span>
                </div>
            </div>
            <span  class="error" style="display:none;"></span>
            <input type="submit" name="ptc-authentication-login" class="ptc-authentication-login" id="ptc-authentication-login" value="<?php esc_attr_e( 'Login', 'pinkbridge-dam' ); ?>">
            <div class="form-loader" id="loader" style="display:none;"><?php esc_html_e( 'Loading..', 'pinkbridge-dam' ); ?></div>
        </form>
        <div class="link-wrapper">
            <?php if ( ! empty( $ptc_access_token ) && ! empty( $ptc_refresh_token ) && $token_status && ! empty( $api_endpoint ) ) : ?>
                <a href="<?php echo esc_url( $admin_page_url ); ?>" title="<?php esc_attr_e( 'Formular Lists', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Formular Lists', 'pinkbridge-dam' ); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url( $nonce_url ); ?>"><?php esc_html_e( 'Update API Endpoint', 'pinkbridge-dam' ); ?></a>
        </div>
    </div>
</div>