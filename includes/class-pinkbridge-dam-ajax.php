<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Pinkbridge_Dam_Ajax {

	/**
	 * __construct function.
	 *
	 * @access public
	*/
	public function __construct() {
		$endpoints = array(
			'ptc_send_data',
			'ptc_dam_media_content',
			'ptc_dam_media',
			'ptc_dam_save_img_url',
			'ptc_dam_store_api_endpoint',
			'ptc_dam_store_login_data'
		);

		foreach ( $endpoints as $action ) {
			add_action( "wp_ajax_{$action}", array( $this, 'pinkbridge_'.$action ) );
		}
	}

	/**
	 * Function call to store API endpoint
	 * @param mixed $nonce
	 * @param mixed $nonce_action
	 * @param mixed $endpoint
	 */
	public function pinkbridge_ptc_dam_store_api_endpoint() {
		// Verify nonce
        $nonce  = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		$nonce_action = isset( $_POST['nonce_action'] ) ? sanitize_text_field( $_POST['nonce_action'] ) : '';
		
		if ( !wp_verify_nonce( wp_unslash($nonce), $nonce_action ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

		$pinkbridge_endpoint = '';
		if (isset($_POST['pinkbridge_endpoint'])) {
			$pinkbridge_endpoint = filter_var($_POST['pinkbridge_endpoint'], FILTER_SANITIZE_URL);
			if (!filter_var($pinkbridge_endpoint, FILTER_VALIDATE_URL)) {
				$pinkbridge_endpoint = '';
			}
		}
        $ptc_api = new PinkBridge_DAM_API();
        $result = $ptc_api->pinkbridge_store_api( $pinkbridge_endpoint );
        wp_send_json( $result );
        wp_die();
		
	}

	/**
	 * Function call to store login tokens
	 * @param mixed $nonce
	 * @param mixed $nonce_action
	 * @param mixed $email
	 * @param mixed $password
	 */
    public function pinkbridge_ptc_dam_store_login_data() {
        // Verify nonce
        $nonce  = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		$nonce_action = isset( $_POST['nonce_action'] ) ? sanitize_text_field( $_POST['nonce_action'] ) : '';
		if ( !wp_verify_nonce( wp_unslash($nonce), $nonce_action ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }
        $logindata = array();
		$pinkbridge_endpoint = '';
		$email = '';

		if (isset($_POST['pinkbridge_endpoint'])) {
			$pinkbridge_endpoint = filter_var($_POST['pinkbridge_endpoint'], FILTER_SANITIZE_URL);
			if (!filter_var($pinkbridge_endpoint, FILTER_VALIDATE_URL)) {
				$pinkbridge_endpoint = ''; // Invalid URL
			}
		}
		$logindata['api_endpoint'] = $pinkbridge_endpoint;
		
		if (isset($_POST['email'])) {
			$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);	
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$email = '';
			}
		}
		$logindata['username'] = $email;
		$logindata['password'] = isset($_POST['password']) ? filter_var($_POST['password'], FILTER_SANITIZE_STRING) : '';

		$ptc_api = new PinkBridge_DAM_API();
		$result = $ptc_api->pinkbridge_create_api_token( $logindata );
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Ajax call to send the page and form data when clicks on sync data button
	 */
	public function pinkbridge_ptc_send_data() {
		global $wpdb;
		$ptc_api = new PinkBridge_DAM_API();
		$result = $ptc_api->pinkbridge_get_forms();
		$data_status = false;
		$data_count = 0;
		$err_msg = 'Something went wrong!! Please Contact to Administrator.';
		$domain_name = PINKBRIDGE_DOMAIN_NAME;
		$json_data = array();
		$form_data = array();
		$json_data['domainName'] = $domain_name;
		if(!empty($result) && isset($result['code']) && $result['code'] == 200){
			if(isset($result['data']) && !empty($result['data']) && isset($result['data']['records']) && !empty($result['data']['records'])){
				$data_status = true;
				$data_count = count($result['data']['records']);
			} else{
				if(!empty($result) && isset($result['message']) && !empty($result['message'])){
					$err_msg = $result['message'];
				} else{
					$err_msg = 'No Records Found.';
				}
			}
		} else{
			if(!empty($result) && isset($result['message']) && !empty($result['message'])){
				$err_msg = $result['message'];
			}
		}
		if($data_status == true){ 
			foreach($result['data']['records'] as $key=>$value){
				if(isset($value['formEncryptedId']) && !empty($value['formEncryptedId'])){
					$search_term = $form_id = $value['formEncryptedId'];
					// @codingStandardsIgnoreStart
					$query = $wpdb->prepare("
						SELECT p.ID
						FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
						WHERE p.post_status = 'publish' AND
							(p.post_title LIKE %s OR p.post_content LIKE %s OR pm.meta_value LIKE %s) GROUP BY p.ID
					", '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%');
					$response = $wpdb->get_col($query);
					// @codingStandardsIgnoreEnd
					$formarr = array();
					$formarr['formId'] = $form_id;
					if(!empty($response)){
						$formarr['pages'] = $response;
					} else{
						$formarr['pages'] = array();
					}
					$form_data[] = $formarr;
				}
			}
			$json_data['formData'] = $form_data;
		}
		if(empty($form_data)){
			$err_msg = 'There are currently no forms assigned to any pages.';
		}
		if(isset($data_status) && $data_status == true && $json_data){
			$dam_res = $ptc_api->pinkbridge_send_dam_data($json_data);
			$return_result = (isset($dam_res['message']) && !empty($dam_res['message']) ? $dam_res['message'] : 'Something went wrong!! Please Contact to Administrator.');
		} else{
			$return_result = $err_msg;
		}
		echo esc_html($return_result);
		wp_die();
	}

	/**
	 * Ajax function call for dam tab
	 */
	public function pinkbridge_ptc_dam_media(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sorry, you do not have permission.' );
		}

		if ( ! class_exists( 'PinkBridge_DAM_API' ) ) {
			require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-api.php';
		}
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce( sanitize_text_field ( wp_unslash ($_POST['nonce'])), 'ptc_dam_media_ajax_nonce')) {
			$err_html = '<div class="pinkbridge-dam-media-wrapper"><div class="pinkbridge-dam-media-error">Invalid nonce</div></div>';
			wp_send_json_error($err_html);
		}

		$multi_img_selection = isset($_POST['multi_img_selection']) ? sanitize_text_field($_POST['multi_img_selection']) : false;
		ob_start();
		$status = '';
		$response = '';
		$code = '';
		$data_status = false;
		$result = array();
		$admin_page_url = menu_page_url('pinkbridge-formular', false);
		$html = '';
		$ptc_api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
		$ptc_access_token = pinkbridge_dam_get_options('ptc_access_token');
		$ptc_refresh_token = pinkbridge_dam_get_options('ptc_refresh_token');
		$token_status = pinkbridge_dam_is_access_token_valid(); 
		if(!empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true){
			$ptc_api = new PinkBridge_DAM_API();
			$result = $ptc_api->pinkbridge_dam_media(1, null);
			if(!empty($result) && isset($result['code']) && $result['code'] == 200){
				if(isset($result['data']) && !empty($result['data'])){
					$data_status = true;
				} else{
					if(!empty($result) && isset($result['message']) && !empty($result['message'])){
						$response = $result['message'];
					} else{
						$response = 'No Records Found.';
					}
				}
			} else{
				if(!empty($result) && isset($result['message']) && !empty($result['message'])){
					$response = $result['message'];
				}
			}
		} else{
			$response = 'Authentication Failed!!';
		} ?>
		<div class="pinkbridge-dam-media-wrapper" dam-selection="<?php echo esc_attr($multi_img_selection); ?>">
			<?php if($data_status == true){ ?>
				<div class="pinkbridge-sidebar-wrapper">
					<?php $dam_data = $result['data'];
					if($dam_data){
						echo wp_kses_post($this->pinkbridge_dam_file_list_tree($dam_data, 0));
					} ?>
				</div>
				<div class="pinkbridge-dam-content-outer">
					<div class="pinkbridge-right-wrapper">
					</div>
				</div>
			<?php } else{ ?>
				<div class="pinkbridge-dam-media-error">
					<?php echo esc_html($response); ?>
				</div>
			<?php } ?>
		</div>
		<?php $html = ob_get_clean();
		wp_send_json_success($html);
		wp_die();
	}

	/**
	 * Ajax function for dam media folder content
	 */
	public function pinkbridge_ptc_dam_media_content() {
		global $wpdb;
		if (!current_user_can('manage_options')) {
            wp_die('Sorry, you do not have permission.');
        }

		if ( ! class_exists( 'PinkBridge_DAM_API' ) ) {
			require_once PINKBRIDGE_DAM_PLUGIN_DIR . 'includes/class-pinkbridge-dam-api.php';
		}
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ptc_dam_media_ajax_nonce' ) ) {
			$err_html = '<div class="pinkbridge-dam-content-wrapper attachments-wrapper"><div class="pinkbridge-right-content-wrapper">Invalid nonce</div></div>';
			wp_send_json_error( $err_html );
		}

		$parent_id = null;
		if (isset($_POST['parent_id'])) {
			$parent_id = filter_var($_POST['parent_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
			if ($parent_id === false) {
				$parent_id = null; // Invalid integer
			}
		}

		ob_start();
		$status = '';
		$response = '';
		$code = '';
		$data_status = false;
		$result = array();
		$admin_page_url = menu_page_url('pinkbridge-formular', false);
		$html = '';

		$ptc_api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
		$ptc_access_token = pinkbridge_dam_get_options('ptc_access_token');
		$ptc_refresh_token = pinkbridge_dam_get_options('ptc_refresh_token');
		$token_status = pinkbridge_dam_is_access_token_valid(); 

		$image_api = $ptc_api_endpoint . PINKBRIDGE_IMAGE_DISPLAY_API;

		if(!empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true){
			$ptc_api = new PinkBridge_DAM_API();
			$result = $ptc_api->pinkbridge_dam_media(0, $parent_id);
			if(!empty($result) && isset($result['code']) && $result['code'] == 200){
				$code = $result['code'];
				if(isset($result['data']) && !empty($result['data'])){
					$data_status = true;
				} else{
					$response = !empty($result['message']) ? $result['message'] : 'No Items Found.';
				}
			} else{
				$response = !empty($result['message']) ? $result['message'] : 'No Items Found.';
			}
			if($data_status == true){
				$dam_data = $result['data'];
				$imgstr = '';
				if($dam_data){ 
					foreach($dam_data as $key => $value){
						$mime_type = explode('/', $value['fileType']);
						$file_mime_type = !empty($mime_type[0]) ? $mime_type[0] : '';

						$imgstr .= '<div class="dam-media-right-bar" data-id="' . esc_attr($value['id']) . '" data-name="' . esc_attr($value['name']) . '" data-type="' . esc_attr($file_mime_type) . '">';
                        $imgstr .= '<div class="dam-media-wrapper" data-id="' . esc_attr($value['id']) . '" data-name="' . esc_attr($value['name']) . '" data-type="' . esc_attr($file_mime_type) . '">';

						$img_url = esc_url($image_api . '/' . $value['id'] . '/' . $value['name']);
						
							if($file_mime_type == 'image'){
								$imgstr .= '<img src="' . $img_url . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($file_mime_type == 'video'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/video.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($value['fileType'] == 'application/pdf'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/pdf.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($value['fileType'] == 'application/msword' || $value['fileType'] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/doc.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($value['fileType'] == 'application/vnd.ms-powerpoint'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/ppt.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($file_mime_type == 'application'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/spreadsheet.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($file_mime_type == 'audio'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/audio.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} elseif($file_mime_type == 'text'){
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/text.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							} else {
								$imgstr .= '<img src="' . esc_url(PINKBRIDGE_DAM_PLUGIN_URL . 'admin/images/default.svg') . '" height="300" width="300" data-src="' . $img_url . '">';
							}
							
						$imgstr .= '</div>';
						$imgstr .= '<div class="dam-file-name">' . esc_html($value['name']) . '</div>';
						$imgstr .= '</div>';
					}
				} else{
					$imgstr .= 'No Item found';
				}
			}
		} else{
			$response = 'Authentication Failed!!';
		}

		echo '<div class="pinkbridge-dam-content-wrapper attachments-wrapper">';
			if (!$data_status) {
				echo '<div class="pinkbridge-dam-media-content-error">' . esc_html($response) . '</div>';
			} else {
				echo '<div class="pinkbridge-right-content-wrapper">' . wp_kses_post($imgstr) . '</div>';
			}
		echo '</div>';

		$res = ob_get_clean();
		wp_send_json($res);
		wp_die();	
	}

	/**
	 * Ajax function call to save the image url into database
	 */
	public function pinkbridge_ptc_dam_save_img_url(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sorry, you do not have permission.' );
		}

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['nonce'])), 'ptc_dam_media_ajax_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$response  = array(
			'status'  => 'error',
			'message' => '',
			'id'      => '',
			'details' => array(),
		);

		$selected_src = '';
		if (isset($_POST['selected_src']) && !empty($_POST['selected_src'])) {
			$selected_src = filter_var($_POST['selected_src'], FILTER_SANITIZE_URL);
			if (!filter_var($selected_src, FILTER_VALIDATE_URL)) {
				$selected_src = '';
			}
		}

		$img_id = 0;
		if (isset($_POST['img_id'])) {
			$img_id = filter_var($_POST['img_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
			if ($img_id === false) {
				$img_id = 0; // Invalid integer
			}
		}
		
		$img_name = isset($_POST['img_name']) ? sanitize_text_field($_POST['img_name']) : '';
		$img_type = isset($_POST['img_type']) ? sanitize_text_field($_POST['img_type']) : '';
		if ( ! empty( $selected_src ) && !empty($img_type) ) {
			$response['details'] = $this->pinkbridge_ptc_add_image( $selected_src, $img_id, $img_name, $img_type );
		}

		if ( count( $response['details'] ) ) {
			$response['status'] = 'success';
		} elseif ( $response['status'] !== 'queue' ) {
			$response['message'] = esc_html__( 'No valid image URLs found', 'pinkbridge-dam' );
		}
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Function to generate listing
	 * @param $arr
	 * @param $level
	 * @return mixed
	 */
	public function pinkbridge_dam_file_list_tree($items, $parent_id = 0) {
        $output = '';
        foreach ($items as $item) {
            if ($item['parentId'] == $parent_id) {
                $has_children = $this->pinkbridge_multi_array_search($items, array('parentId' => $item['id']));
                $class = $has_children ? 'has-child' : '';
                $display_name = $has_children ? '<span>' . esc_html($item['name']) . '</span>' : esc_html($item['name']);

                $output .= sprintf(
                    "<li class='%s dam_folder' data-id='%d'>%s%s</li>",
                    esc_attr($class),
                    esc_attr($item['id']),
                    $display_name,
                    $this->pinkbridge_dam_file_list_tree($items, $item['id'])
                );
            }
        }

        return empty($output) ? '' : "<ul class='open-tree'>" . $output . "</ul>";
    }

	/**
	 * Function to search array by key and value
	 * @param array $array
	 * @param $search
	 * @return array
	 */
	public function pinkbridge_multi_array_search($array, $search) {
		$result = array();
		foreach ($array as $key => $value) {
			foreach ($search as $k => $v) {
				if (!isset($value[$k]) || $value[$k] != $v) {
					continue 2;
				}
			}
			$result[] = $value;
		}
		return $result;
	}

	/**
	 * Insert image from DAM tab
	 * @param $url
	 * @param $img_id
	 * @param $img_name
	 * @return     array
	 */
	public function pinkbridge_ptc_add_image( $url, $img_id, $img_name, $img_type ) {
		global $wpdb;
		
		$parent_id = '';

		$result = array(
            'url'       => esc_url_raw($url),
            'message'   => '',
            'status'    => 'error',
            'id'        => '',
            'edit_link' => '',
        );

		if (empty($url) || $img_id === null) {
            $result['message'] = esc_html__('Invalid image URL', 'pinkbridge-dam');
            return $result;
        }

		$ptc_api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
		$file_api = $ptc_api_endpoint . PINKBRIDGE_FILE_DOWNLOAD_API;
		$download_url = esc_url_raw($file_api . '/' . $img_id);
		// @codingStandardsIgnoreStart
		$check_image_qry = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ptc_dam_img_id' AND meta_value = %d",
			$img_id
		);
		$exist = $wpdb->get_var( $check_image_qry );
		// @codingStandardsIgnoreEnd
		if ($exist > 0) {
			if(!get_post_status($exist)) {
				// @codingStandardsIgnoreStart
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ( %s )",
						$exist
					)
				);
				// @codingStandardsIgnoreEnd
				$exist = '';
			} else{
				$this->pinkbridge_ptc_update_file_data( $url, $img_id, $img_name, $img_type, $exist );
			}
			
		}
		$width = $height = 800;
		if ( empty($exist) ) {
			$check_filetype   = wp_check_filetype( basename( $img_name ), null );
			$attachment_image = array(	
				'post_title'     => sanitize_text_field($img_name),
                'post_mime_type' => sanitize_text_field($img_type) . '/url',
                'guid'           => strlen($url) > 255 ? '' : esc_url_raw($url),
                'post_status'    => 'inherit',
			);
			$attachment_id = wp_insert_attachment( $attachment_image, $url, $parent_id, true );
			
			if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
				$result['id']        = $attachment_id;
				$result['status']    = 'success';
				$result['message']   = esc_html__( 'Successful', 'pinkbridge-dam' );
				$result['edit_link'] = esc_url( add_query_arg( array(
					'post'   => $attachment_id,
					'action' => 'edit'
				), admin_url( 'post.php' ) ) );
				
				$this->pinkbridge_update_attachment_metadata( $attachment_id, $url, null, $width, $height, $img_name, $img_type, $img_id );
				update_post_meta($attachment_id, '_ptc_dam_external_url', esc_url_raw($url));
                update_post_meta($attachment_id, '_ptc_dam_download_url', esc_url_raw($download_url));
				update_post_meta( $attachment_id,'_ptc_dam_img_id', $img_id);
				  
			} else {
				$result['message'] = $attachment_id->get_error_message();
			}
		} else {
			$result['id'] = $exist;
            $result['status'] = 'success';
            $result['message'] = esc_html__('Image exists', 'pinkbridge-dam');
            $result['edit_link'] = esc_url(add_query_arg(array(
                'post'   => $exist,
                'action' => 'edit',
            ), admin_url('post.php')));
		}
		return $result;
	}

	/**
	 * Insert image from DAM tab
	 * @param $url
	 * @param $img_id
	 * @param $img_name
	 */
	function pinkbridge_ptc_update_file_data( $url, $img_id, $img_name, $img_type, $attachment_id ){
		global $wpdb;

		$attachment_metadata = wp_get_attachment_metadata($attachment_id);
		$attachment_metadata['file'] = $url;

		$post_data = array(
			'ID'         => $attachment_id,
			'post_title' => sanitize_text_field($img_name),
			'post_name'  => sanitize_title($img_name),
			'guid'       => esc_url_raw($url),
		);
	
		// Update the post
		wp_update_post($post_data);

		// Update the attachment metadata
		wp_update_attachment_metadata($attachment_id, $attachment_metadata);
	}

	/**
	 * Function to update attachment metadata
	 * @param $attachment_id
	 * @param $url
	 * @param $is_ali_cdn
	 * @param $width
	 * @param $img_name
	 */
	private function pinkbridge_update_attachment_metadata( $attachment_id, $url, $is_ali_cdn, $width, $height, $img_name, $img_type, $img_id ) {
		if ( ! get_post_meta( $attachment_id, '_ptc_dam_external_url', true ) ) {
			update_post_meta( $attachment_id, '_ptc_dam_external_url', $url );
		}
		
		$ptc_api_endpoint = pinkbridge_dam_get_options('ptc_api_endpoint');
		$file_api = $ptc_api_endpoint . PINKBRIDGE_FILE_DOWNLOAD_API;
		$file_url = $file_api.'/'.$img_id;
		update_post_meta( $attachment_id, '_ptc_dam_download_url', esc_url($file_url) );

		if($img_type == 'image'){
			$headers = get_headers($url, true);
			
			$wp_sizes    = $this->pinkbridge_ptc_get_img_sizes();
			$image_sizes = array();
			$pathinfo    = pathinfo( $url );
			if ( ! empty( $pathinfo['extension'] ) ) {
				$common_sizes = array(
					'thumbnail'    => 150,
					'medium'       => 300,
					'medium_large' => 768,
					'large'        => 1024
				);
				foreach ( $common_sizes as $size_name => $size_width ) {
					if ( $is_ali_cdn ) {
						/*Ali cdn image size format: original-image-name.jpg_100x100.jpg*/
						$size_url = $url . "_{$size_width}x{$size_width}.{$pathinfo['extension']}";
					} else {
						/*WordPress image size format: original-image-name-100x100.jpg*/
						$size_url = apply_filters( 'pinkbridge_ptc_dam_image_size_url', substr( $url, 0, strlen( $url ) - strlen( $pathinfo['extension'] ) - 1 ) . "-{$size_width}x{$size_width}.{$pathinfo['extension']}", $url, $size_width );
					}
					$is_valid_image_url = true;
					if ( ! $is_valid_image_url ) {
						/*Use original url if the image size url is invalid*/
						$size_url = $url;
					}
					$image_sizes[ $size_name ] = array(
						'url'    => esc_url($size_url),
						'width'  => $size_width,
						'height' => $size_width
					);
				}
			}
			if ( ! isset( $image_sizes['large'] ) ) {
				$image_sizes['large'] = array(
					'url'    => esc_url($url),
					'width'  => $width,
					'height' => $height
				);
			} else {
				$image_sizes['full'] = array(
					'url'    => esc_url($url),
					'width'  => $width,
					'height' => $height
				);
			}
			/*Build attachment metadata*/
			$attach_data = array(
				'filesize' => isset($headers['Content-Length']) ? $headers['Content-Length'] : '',
				'mime_type' => isset($headers['Content-Type']) ? $headers['Content-Type'] : '',
				'file'       => esc_url($url),
				'width'      => $width,
				'height'     => $height,
				'sizes'      => array(),
				'image_meta' => array(
					'aperture'          => '0',
					'credit'            => '',
					'camera'            => '',
					'caption'           => '',
					'created_timestamp' => '0',
					'copyright'         => '',
					'focal_length'      => '0',
					'iso'               => '0',
					'shutter_speed'     => '0',
					'title'             => $img_name,
					'orientation'       => '0',
					'keywords'          => array(),
				),
			);
			foreach ( $wp_sizes as $size => $props ) {
				$select_size = $this->pinkbridge_ptc_dam_select_size( $props, $image_sizes );
				if ( ! empty( $select_size ) ) {
					$check_filetype                  = wp_check_filetype( basename( $select_size['url'] ), null );
					$attach_data['sizes']["{$size}"] = array(
						'file'      => basename( $select_size['url'] ),
						'width'     => $select_size['width'],
						'height'    => $select_size['height'],
						// 'mime-type' => $check_filetype['type'],
						'mime-type' => $img_type.'/url',
					);
				}
			}
			if ( isset( $attach_data['sizes']['full'] ) ) {
				unset( $attach_data['sizes']['full'] );
			}
			wp_update_attachment_metadata( $attachment_id, $attach_data );
		} else{
			
			$headers = get_headers($file_url, true);

			if(!empty($headers)){
				$fileExtension = $img_type;
				if(isset($headers['Content-Type']) && $headers['Content-Type']){
					$parts = explode('/', $headers['Content-Type']);
					$fileExtension = end($parts);
				}
				
				$attach_data = array(
					'file'       => esc_url($url),
					'filesize' => isset($headers['Content-Length']) ? $headers['Content-Length'] : '',
					'mime_type' => isset($headers['Content-Type']) ? $headers['Content-Type'] : '',
					'fileformat' => $fileExtension,
					'Server' => isset($headers['Server']) ? $headers['Server'] : '',
					'X_Powered_By' => isset($headers['X-Powered-By']) ? $headers['X-Powered-By'] : '',
					'audio' => array(
						'dataformat' => $fileExtension
					)
				);

				wp_update_attachment_metadata($attachment_id, $attach_data);
			}
		}
	}

	/**
	 * Function to select image sizes
	 * @param $size
	 * @param array $image_sizes
	 *
	 * @return bool|mixed
	 */
	private function pinkbridge_ptc_dam_select_size( $size, $image_sizes = array() ) {
		if ( empty( $image_sizes ) ) {
			return $size;
		}

		$min_size = $max_size = false;
		
		foreach ( $image_sizes as $props ) {
			if((int) $size['width'] == (int) $props['width']) {
				return $props;
			}
			
			if(intval($size['width']) < intval($props['width']) && (! $min_size || intval($min_size['width']) > intval($props['width']))) {
				$min_size = $props;
			}
			
			if (!$max_size || (intval($max_size['width']) < intval($props['width']))) {
				$max_size = $props;
			}
		}
		return ! $min_size ? $max_size : $min_size;
	}

	/**
	 * Generate sizes if any
	 *
	 * @return array
	 */
	private function pinkbridge_ptc_get_img_sizes() {
		global $_wp_additional_image_sizes;
		
		$sizes = array();
		
		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}
		return $sizes;
	}
}