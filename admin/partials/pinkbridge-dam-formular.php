<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$api_endpoint = pinkbridge_dam_get_options();
$ptc_api = new PinkBridge_DAM_API();
$result = $ptc_api->pinkbridge_get_forms();
$data_status = false;
$data_count = 0;
$err_msg = __( 'Something went wrong!! Please contact the administrator.', 'pinkbridge-dam' );
$admin_page_url = menu_page_url('pinkbridge-formular', false);
$nonce = wp_create_nonce('ptc-dam-module');
$api_nonce_url = add_query_arg(array(
    'module' => 'api-endpoint',
    '_wpnonce' => $nonce,
), $admin_page_url);
$auth_nonce_url = add_query_arg(array(
    'module' => 'refresh-authentication',
    '_wpnonce' => $nonce,
), $admin_page_url);
if(!empty($result) && isset($result['code']) && $result['code'] == 200){
    if(isset($result['data']) && !empty($result['data']) && isset($result['data']['records']) && !empty($result['data']['records'])){
        $data_status = true;
        $data_count = count($result['data']['records']);
    } else{
        $err_msg = ! empty( $result['message'] ) ? $result['message'] : __( 'No Records Found.', 'pinkbridge-dam' );
    }
} elseif(!empty($result) && isset($result['message']) && !empty($result['message'])){
    $err_msg = $result['message'];
} ?>

<div class="pinkbridge-formular-outer">
    <div class="container">
        <div class="pinkbridge-formular-section">
            <div class="title-btn-wrapper">
                <h1><?php esc_html_e( 'Formular Lists', 'pinkbridge-dam' ); if($data_count > 0) { echo esc_attr(" (".$data_count.")"); } ?></h1>
                <p class="note"><?php echo wp_kses_post( __( 'Note: Kindly copy the shortcode by selecting the copy icon and then paste it onto the relevant page.<br/> Avoid directly embedding the formular shortcode into files or templates. These shortcodes should only be utilized within the admin page (backend) or section.', 'pinkbridge-dam' ) ); ?></p>
                <div class="button-wrapper">
                    <div class="update-api-button">
                        <a class="pinkbridge-btn" href="<?php echo esc_url( $api_nonce_url ); ?>" title="<?php esc_attr_e( 'Update API Endpoint', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Update API Endpoint', 'pinkbridge-dam' ); ?></a>
                    </div>
                    <div class="update-auth-button">
                        <a class="pinkbridge-btn" href="<?php echo esc_url( $auth_nonce_url ); ?>" title="<?php esc_attr_e( 'Refresh Authentication', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Refresh Authentication', 'pinkbridge-dam' ); ?></a>
                    </div>
                    <div class="send-form-data">
                        <a class="pinkbridge-btn" href="javascript:void(0)" id="ptc_send_data" title="<?php esc_attr_e( 'Sync Form Data', 'pinkbridge-dam' ); ?>"><?php esc_html_e( 'Sync Form Data', 'pinkbridge-dam' ); ?></a>
                    </div>
                </div>
            </div>
            <?php if($data_status == true){ ?>
                <div class="table-wrapper">
                    <div class="table-wrapper-inner">
                        <table>
                            <tr>
                                <th><?php esc_html_e( 'ID', 'pinkbridge-dam' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'pinkbridge-dam' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'pinkbridge-dam' ); ?></th>
                                <th><?php esc_html_e( 'Shortcode', 'pinkbridge-dam' ); ?></th>
                            </tr>
                            <?php foreach($result['data']['records'] as $key=>$value){ ?>
                                <tr>
                                    <td><?php echo absint($key+1); ?></td>
                                    <td><?php echo isset( $value['formName'] ) ? esc_html( $value['formName'] ) : '-'; ?></td>
                                    <td><?php echo isset( $value['type'] ) ? esc_html( $value['type'] ) : '-'; ?></td>
                                    <td class="has-icon">
                                        <span class="shortcode"><?php echo isset( $value['formEncryptedId'] ) ? '[pinkbridge_dam_formular id="' . esc_html( $value['formEncryptedId'] ) . '"]' : '-'; ?></span>
                                        <a href="#" title="<?php esc_attr_e( 'Copy Short Code', 'pinkbridge-dam' ); ?>" class="copy-code">
                                            <img src="<?php echo esc_url( PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/copy-icon.svg' ); ?>" alt="<?php esc_attr_e( 'Copy Icon', 'pinkbridge-dam' ); ?>" class="copy-icon" />
                                            <span class="custom-tooltip"><?php esc_html_e( 'Copied !!', 'pinkbridge-dam' ); ?></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            <?php } else{ ?>
                <div class="error-wrap">
                    <?php echo esc_html($err_msg); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>