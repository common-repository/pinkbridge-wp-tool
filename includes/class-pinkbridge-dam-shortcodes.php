<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PinkBridge_DAM_API' ) ) {
    require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-api.php';
}
class PinkBridgeDAMshortcodes {
    function __construct() {
        add_shortcode('pinkbridge_dam_formular', array($this, 'pinkbridge_formular_frame_function_Call'));
    }

    /**
     * Function to display formular shortcode
     */
    function pinkbridge_formular_frame_function_Call($raw_args=array(), $content = null) {
        $raw_args = shortcode_atts( array(
            'id' => null,
        ), $raw_args );

        $err_status = false;
        $err_msg = '';
        $content = '';

        ob_start(); ?>
        <div class="ptc-dam-formular-wrapper">
            <?php if(!empty($raw_args) && isset($raw_args['id']) && !empty($raw_args['id'])) {
                $pinkbridge_options = pinkbridge_dam_get_options();
                $domain_name = PINKBRIDGE_DOMAIN_NAME;
                $page_id = get_the_ID();
                $err_status = true;
                $err_msg = 'Authentication Failed!!';

                if($pinkbridge_options && 
                isset( $pinkbridge_options['ptc_api_endpoint'] ) && !empty($pinkbridge_options['ptc_api_endpoint']) && 
                isset( $pinkbridge_options['ptc_access_token'] ) && !empty($pinkbridge_options['ptc_access_token']) && 
                isset( $pinkbridge_options['ptc_refresh_token'] ) && !empty($pinkbridge_options['ptc_refresh_token']) && 
                isset( $pinkbridge_options['token_status'] ) && $pinkbridge_options['token_status'] == 'valid' ){ 

                    $ptc_api = new PinkBridge_DAM_API();
                    $result = $ptc_api->pinkbridge_get_forms($raw_args['id']);

                    if(!empty($result) && isset($result['code']) && $result['code'] == 200 && isset($result['data']) && !empty($result['data']) && isset($result['data']['records']) && !empty($result['data']['records']) && isset($result['data']['records'][0]) && !empty($result['data']['records'][0])){
                        $err_status = false;
                    } else{
                        $err_msg = ! empty( $result['message'] ) ? $result['message'] : 'Form not found!';
                    }                
                } else{
                    $err_status = true;
                    $err_msg = 'Authentication Failed!!';
                }
            } else{
                $err_status = true;
                $err_msg = 'The shortcode used appears to be incorrect. Please use the correct one.';
            } 
            if($err_status == true){ ?>
                <div class="formular-shortcode-error"><?php echo esc_html($err_msg); ?></div>
            <?php } else{
                $formSource = $result['data']['records'][0]['formSource']; ?>
                <div style="position: relative; height: 100vh" class="ptc-iframe-wrapper">
                    <iframe id="Formular" class="ptc-resize-iframe" frameborder="0" src="<?php echo esc_url($formSource); ?>" style="position: absolute; width: 100%; height: 100%"></iframe>
                </div>
            <?php } ?>
        </div>
        <script type="text/javascript">
            function resizeIframes() {
                var page_id = "<?php echo (int)$page_id; ?>";
                var domain_name = "<?php echo esc_attr($domain_name); ?>";
                var myArray = {page_id: page_id, domain_name: domain_name};
                var postdata = page_id +', '+domain_name;
                jQuery(".ptc-resize-iframe").each(function () {
                    var iframe = this;
                    var iframeContent = iframe.contentWindow.postMessage(postdata, '*');
                });
            }
            jQuery(".ptc-resize-iframe").on("load", function () {
                resizeIframes();
            });
        </script>
        <?php
        $content .= ob_get_clean();
        return $content;
    }
}
$shortcodes = new PinkBridgeDAMshortcodes();