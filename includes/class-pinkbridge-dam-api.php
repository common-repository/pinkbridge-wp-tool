<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PinkBridge_DAM_API {

	/**
	 * Contains cURL instance
	 *
	 * @access protected
	 */
	protected $token;

	protected $refreshToken;

	/**
	 * Contains API headers
	 *
	 * @access protected
	 */
	protected $headers;

	protected $api_url;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Setup Headers
        $this->headers = array(
			'Content-Type' => 'application/json', 
            'Accept'       => 'application/json',
        );
		$this->api_url = pinkbridge_dam_get_options('ptc_api_endpoint');
		$this->token = pinkbridge_dam_get_options('ptc_access_token');
		$this->refreshToken = pinkbridge_dam_get_options('ptc_refresh_token');
	}

	/**
	 * Make an HTTP request to the API.
	 *
	 * @internal
	 * @param mixed $url API endpoint
	 * @param mixed  $request  request array
	 * @param mixed  $data  arguments
	 *
	 */
	public function pinkbridge_ptc_dam_make_request( $url, $request ) {
		$response = array(
			'status' => '',
			'message' => '',
			'code' => '',
			'data' => array(),
		);

		$result = wp_remote_post($url, $request);
		$httpcode = wp_remote_retrieve_response_code( $result );
		$response['code'] = $httpcode;

		if ( is_wp_error( $result ) ) {
			$response['status'] = 'error';
			$response['message'] = $result->get_error_message();
		} else {
			$api_response = json_decode( wp_remote_retrieve_body( $result ) );

			if($httpcode == 200){
				$response['status'] = 'success';
				$response['data'] = isset($api_response->data) ? json_decode(wp_json_encode($api_response->data), true) : array();
			} else{
				$response['status'] = 'error';
				
				if ($api_response && is_object($api_response) && isset($api_response->message) && !empty($api_response->message)) {
					$response['message'] = $api_response->message;
				} elseif($result && is_array($result) && isset($result['response']['message']) && !empty($result['response']['message'])){
					$response['message'] = $result['response']['message'];
					$response['code'] = $httpcode ? $httpcode : $result['response']['code'];
				}					
			}
		}
		return $response;
	}

	/**
	 * Get Access Token
	 *
	 * @access public
	 * @return array
	 */
	public function pinkbridge_create_api_token( $data=array() ) {
        // Get Store channel
		$response = array(
			'status'  => '',
			'message' => '',
			'code'    => '',
		);

		$pinkbridge_options = pinkbridge_dam_get_options();
		if(empty($pinkbridge_options)){
			$pinkbridge_options = array();
		}

		$api_endpoint = isset($data['api_endpoint']) ? $data['api_endpoint'] : ''; 
		$email = isset($data['username']) ? $data['username'] : ''; 
		$password = isset($data['password']) ? $data['password'] : ''; 

		if(empty($api_endpoint)){
			$response['status'] = 'error';
			$response['message'] = 'Please Enter API endpoint.';
		} elseif(empty($email) || empty($password)){
			$response['status'] = 'error';
			$response['message'] = 'Please Enter Email and Password';
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$response['status'] = 'error';
			$response['message'] = 'Invalid Email Format';
		} else{
			$token_api_url = $api_endpoint.PINKBRIDGE_LOGIN_API;
			
			$login_data = array(
				'email'      => $email,
				'password'   => $password,
				'domainName' => PINKBRIDGE_DOMAIN_NAME,
			);

			$logindata = wp_json_encode($login_data);
			$request = array(
				'timeout' => 60,
				'body'        => $logindata,
				'headers' => $this->headers
			);

			$data_response = $this->pinkbridge_ptc_dam_make_request($token_api_url, $request);
			
			if(!empty($data_response) && isset($data_response['code']) && $data_response['code'] == 200){
				if(isset($data_response['data']) && !empty($data_response['data'])){
					$token = isset($data_response['data']['token']) ? $data_response['data']['token'] : '';
					$refreshToken = isset($data_response['data']['refreshToken']) ? $data_response['data']['refreshToken'] : '';

					$pinkbridge_options['ptc_access_token'] = $token;
					$pinkbridge_options['ptc_refresh_token'] = $refreshToken;
					$pinkbridge_options['token_status'] = 'valid';
					pinkbridge_dam_update_options($pinkbridge_options);
				}
			}

			if($data_response){
				$response = $data_response;
			}
		}
		return $response;
	}

	/**
	 * Store API endpoint
	 *
	 * @access public
	 * @return array
	 */
	public function pinkbridge_store_api( $api_endpoint, $data=array() ) {
		$response = array(
			'status'  => '',
			'message' => '',
		);

		$pinkbridge_options = pinkbridge_dam_get_options();

		if(empty($pinkbridge_options)){
			$pinkbridge_options = array();
		}

		if(!empty($api_endpoint)){
			$pinkbridge_options['ptc_api_endpoint'] = $api_endpoint;
			pinkbridge_dam_update_options($pinkbridge_options);
			$response['status'] = 'success';
		} else{
			$response['status'] = 'error';
			$response['message'] = 'Please Enter API Endpoint.';
		}

		return $response;
	}

	/**
	 * Function to get all form data and for specific form data from $id
	 * @access public
	 * @return array
	 */
	public function pinkbridge_get_forms( $id=null ) {
		$response = array(
			'status'  => '',
			'message' => '',
			'code'    => '',
			'data'    => array(),
		);

		$ptc_api_endpoint = $this->api_url;
		$ptc_access_token = $this->token;
		$ptc_refresh_token = $this->refreshToken;

		$token_status = pinkbridge_dam_is_access_token_valid();

		if(!empty($ptc_api_endpoint) && !empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true){
			$api_url = $ptc_api_endpoint.PINKBRIDGE_FORMULAR_API;

			$postdata = array(
				"pageNo" => 1,
				"pageSize" => 0,
				"sortOrder" => 0,
				'domainName' => PINKBRIDGE_DOMAIN_NAME
			);

			if(!empty($id)){
				$postdata["keywordSearch"] = $id;
			}
			$indexParameter = wp_json_encode($postdata);
			$authorization = "Bearer ".$ptc_access_token; 
			$request = array(
				'timeout' => 60,
				'body'        => $indexParameter,
				'headers' => array(
					'Content-Type' => 'application/json', 
					'Accept'       => 'application/json',
					'Authorization' => $authorization
				)
			);
			$result = wp_remote_post($api_url, $request);
			$httpcode = wp_remote_retrieve_response_code( $result );
			$response['code'] = $httpcode;
			if ( is_wp_error( $result ) ) {
				$response['status'] = 'error';
				$response['message'] = $result->get_error_message();
			} else {
				$api_response = json_decode( wp_remote_retrieve_body( $result ) );
				if($httpcode == 200){
					$response['status'] = 'success';
					$form_data = array();
					if(isset($api_response->data) && !empty($api_response->data) ){
						$form_data = json_decode(wp_json_encode($api_response->data), true);
					}
					$response['data'] = $form_data;
					$response['message'] = 'No records found';
				} elseif($httpcode == 498){
					$refresh_token_response = $this->pinkbridge_refresh_api_token();
					if($refresh_token_response && isset($refresh_token_response['code']) && $refresh_token_response['code'] == 200){
						$response = $this->pinkbridge_get_forms($id);
					} else{
						$response = $refresh_token_response ? $refresh_token_response : array(
							'status'  => 'error',
							'code'    => $httpcode,
							'message' => 'Authentication Failed!!'
						);
					}
				}else{
					$response['status'] = 'error';
					if ($api_response && is_object($api_response) && isset($api_response->message) && !empty($api_response->message)) {
						$response['message'] = $api_response->message;
					} elseif($result && is_array($result) && isset($result['response']['message']) && !empty($result['response']['message'])){
						$response['message'] = $result['response']['message'];
						$response['code'] = $httpcode ? $httpcode : $result['response']['code'];
					} else{
						$response['message'] = 'Something went wrong!! Please Contact to Administrator.';
					}
				}
			}
		} else{
			$response['message'] = 'Authentication Failed!! Please Contact to Administrator.';
		}
		return $response;
	}

	/**
	 * Get Access Token
	 *
	 * @access public
	 * @return string
	 */
	public function pinkbridge_refresh_api_token() {
		$response = array(
			'status'  => '',
			'message' => '',
			'code'    => '',
		);
		
		$pinkbridge_options = pinkbridge_dam_get_options();
		if(empty($pinkbridge_options)){
			$pinkbridge_options = array();
		}
		$ptc_api_endpoint = $this->api_url;
		$ptc_access_token = $this->token;
		$ptc_refresh_token = $this->refreshToken;
		$token_status = pinkbridge_dam_is_access_token_valid();

		if($ptc_api_endpoint && $ptc_access_token && $ptc_refresh_token && $token_status == true){
			$token_api_url = $ptc_api_endpoint.PINKBRIDGE_REFRESH_TOKEN.'?token='.$ptc_refresh_token;
			$login_data = array(
				'token' => $ptc_refresh_token, 
				'domainName' => PINKBRIDGE_DOMAIN_NAME
			);
			$logindata = wp_json_encode($login_data);
			$request = array(
				'timeout' => 60,
				'body'        => $logindata,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json'
				)
			);
			$data_response = $this->pinkbridge_ptc_dam_make_request($token_api_url, $request);
			if(!empty($data_response) && isset($data_response['code']) && $data_response['code'] == 200){
				if(isset($data_response['data']) && !empty($data_response['data'])){
					$token = isset($data_response['data']['token']) ? $data_response['data']['token'] : '';
					$refreshToken = isset($data_response['data']['refreshToken']) ? $data_response['data']['refreshToken'] : '';
					if($token && $refreshToken){
						$pinkbridge_options['ptc_access_token'] = $token;
						$pinkbridge_options['ptc_refresh_token'] = $refreshToken;
						$pinkbridge_options['token_status'] = 'valid';
						pinkbridge_dam_update_options($pinkbridge_options);
					}
				}
			}
			if($data_response){
				$response = $data_response;
			}
		} else{
			$response['status'] = 'error';
			$response['message'] = 'Authentication Failed!!';
		}
		return $response;
	}

	/**
	 * Function to get dam media data and for specific filetype and parentId
	 * @param mixed $filtype // 0 - all file type except folder, 1 - Folder, 2 - images, 3 - videos, 4 - documents
	 * @param mixed $parentId
	 * @access public
	 * @return array
	 *
	 */
	public function pinkbridge_dam_media($filetype=0, $parentId=null) {
		$response = array(
			'status'  => '',
			'message' => '',
			'code'    => '',
			'data'    => array(),
		);

		$ptc_api_endpoint = $this->api_url;
		$ptc_access_token = $this->token;
		$ptc_refresh_token = $this->refreshToken;
		$token_status = pinkbridge_dam_is_access_token_valid();

		if(!empty($ptc_api_endpoint) && !empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true){
			$api_url = $ptc_api_endpoint.PINKBRIDGE_DAM_MEDIA_API;
			$postdata = array(
				"fileType"=> $filetype,
				"date"=> "",
				"parentId" => $parentId
			);
			$indexParameter = wp_json_encode($postdata);
			
			$authorization = "Bearer ".$ptc_access_token; 
			$request = array(
				'timeout' => 60,
				'body'        => $indexParameter,
				'headers' => array(
					'Content-Type' => 'application/json', 
					'Accept'       => 'application/json',
					'Authorization' => $authorization
				)
			);
			$result = wp_remote_post($api_url, $request);
			$httpcode = wp_remote_retrieve_response_code( $result );
			$response['code'] = $httpcode;

            if ( is_wp_error( $result ) ) {
				$response['status'] = 'error';
				$response['message'] = $result->get_error_message();
			} else {
				$api_response = json_decode( wp_remote_retrieve_body( $result ) );
				if($httpcode == 200){
					$response['status'] = 'success';
					$form_data = array();
					if(isset($api_response->data) && !empty($api_response->data) ){
						$form_data = json_decode(wp_json_encode($api_response->data), true);
					}
					$response['data'] = $form_data;
					$response['message'] = 'No records found';
				} elseif($httpcode == 498){
					$refresh_token_response = $this->pinkbridge_refresh_api_token();
					if($refresh_token_response && isset($refresh_token_response['code']) && $refresh_token_response['code'] == 200){
						$response = $this->pinkbridge_dam_media();
					} else{
						if($refresh_token_response){
							$response = $refresh_token_response;	
						} else{
							$response['status'] = 'error';
							$response['code'] = $httpcode;
							$response['message'] = 'Authentication Failed!!';
						}
					}
				}else{
					$response['status'] = 'error';
					if ($api_response && is_object($api_response) && isset($api_response->message) && !empty($api_response->message)) {
						$response['message'] = $api_response->message;
					} elseif($result && is_array($result) && isset($result['response']['message']) && !empty($result['response']['message'])){
						$response['message'] = $result['response']['message'];
						$response['code'] = $httpcode ? $httpcode : $result['response']['code'];
					} else{
						$response['message'] = 'Something went wrong!! Please Contact to Administrator.';
					}
				}
			}
		} else{
			$response['message'] = 'Authentication Failed!! Please Contact to Administrator.';
		}
		return $response;
	}
	
	/**
	 * Function to send all the pages with attached form with id to API server in json format
	 * @access public
	 * @return array
	 */
	public function pinkbridge_send_dam_data( $dam_data ) {
		$response = array(
			'status'  => '',
			'message' => '',
			'code'    => '',
		);

		$ptc_api_endpoint = $this->api_url;
		$ptc_access_token = $this->token;
		$ptc_refresh_token = $this->refreshToken;
		$token_status = pinkbridge_dam_is_access_token_valid();

		if(!empty($ptc_api_endpoint) && !empty($ptc_access_token) && !empty($ptc_refresh_token) && $token_status == true && !empty($dam_data)){
			$api_url = $ptc_api_endpoint.PINKBRIDGE_SEND_FORM_DATA;
			$indexParameter = wp_json_encode($dam_data);
			$authorization = "Bearer ".$ptc_access_token; 
			$request = array(
				'timeout' => 60,
				'body'        => $indexParameter,
				'headers' => array(
					'Content-Type' => 'application/json', 
					'Accept'       => 'application/json',
					'Authorization' => $authorization
				),
				'method' => 'PUT'
			);

			$result = wp_remote_post($api_url, $request);
			$httpcode = wp_remote_retrieve_response_code( $result );
			$response['code'] = $httpcode;
			
			if ( is_wp_error( $result ) ) {
				$response['status'] = 'error';
				$response['message'] = $result->get_error_message();
			} else {
				$api_response = json_decode( wp_remote_retrieve_body( $result ) );

				if($httpcode == 200){
					$response['status'] = 'success';
					$response['message'] = 'Form Data Synced Successfully!!';	
				} elseif($httpcode == 498){
					$refresh_token_response = $this->pinkbridge_refresh_api_token();

					if($refresh_token_response && isset($refresh_token_response['code']) && $refresh_token_response['code'] == 200){
						$response = $this->pinkbridge_send_dam_data($dam_data);
					} else{

						if($refresh_token_response){
							$response = $refresh_token_response;	
						} else{
							$response['status'] = 'error';
							$response['code'] = $httpcode;
							$response['message'] = 'Authentication Failed!!';
						}
					}
				}else{
					$response['status'] = 'error';

					if ($api_response && is_object($api_response) && isset($api_response->message) && !empty($api_response->message)) {
						$response['message'] = $api_response->message;
					} elseif($result && is_array($result) && isset($result['response']['message']) && !empty($result['response']['message'])){
						$response['message'] = $result['response']['message'];
						$response['code'] = $httpcode ? $httpcode : $result['response']['code'];
					} else{
						$response['message'] = 'Something went wrong!! Please Contact to Administrator.';
					}
				}
			}
		} else{
			$response['message'] = 'Authentication Failed!! Please Contact to Administrator.';
		}
		return $response;
	}
}
