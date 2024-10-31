<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
class Pinkbridge_Dam {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pinkbridge_Dam_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PINKBRIDGE_DAM_VERSION' ) ) {
			$this->version = PINKBRIDGE_DAM_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'pinkbridge-dam';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->add_filters_actions();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pinkbridge_Dam_Loader. Orchestrates the hooks of the plugin.
	 * - Pinkbridge_Dam_i18n. Defines internationalization functionality.
	 * - Pinkbridge_Dam_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pinkbridge-dam-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pinkbridge-dam-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-pinkbridge-dam-admin.php';

		$this->loader = new Pinkbridge_Dam_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pinkbridge_Dam_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Pinkbridge_Dam_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'pinkbridge_load_plugin_textdomain' );

	}

	/**
	 * add filter and action in admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function add_filters_actions() {
		/*Filter attachment url and image source set*/
		add_filter( 'wp_get_attachment_url', array( $this, 'pinkbridge_ptc_dam_wp_get_attachment_url'), 10, 2 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'pinkbridge_ptc_dam_wp_calculate_image_srcset'), 10, 5 );
		/*Jetpack - Photon CDN*/
		add_filter( 'jetpack_photon_skip_image', array( $this, 'pinkbridge_ptc_dam_jetpack_photon_skip_image' ), 10, 3 );
		/*WPML*/
		add_action( 'wpml_after_duplicate_attachment', array( $this, 'pinkbridge_ptc_dam_wpml_after_duplicate_attachment' ), 10, 2 );
		/**Woocommerce */
		add_action( 'woocommerce_product_import_before_process_item', function () {
			remove_action( 'pre_get_posts', [ $this, 'pinkbridge_ptc_dam_search_exmage_url_when_import_product' ] );
			add_action( 'pre_get_posts', [ $this, 'pinkbridge_ptc_dam_search_exmage_url_when_import_product' ] );
		} );
		add_action( 'woocommerce_product_import_inserted_product_object', function () {
			remove_action( 'pre_get_posts', [ $this, 'pinkbridge_ptc_dam_search_exmage_url_when_import_product' ] );
		} );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Pinkbridge_Dam_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pinkbridge_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pinkbridge_enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'pinkbridge_add_menu_page', 20 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'pinkbridge_ptc_dam_plugin_row_meta', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pinkbridge_Dam_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @param $url
	 * @param $id
	 *
	 * @return mixed
	 */
	public function pinkbridge_ptc_dam_wp_get_attachment_url( $url, $id ) {
		if ( ! get_post_meta( $id, '_ptc_dam_external_url', true ) ) {
			return $url;
		}
		$post = get_post( $id );
		if ( $post && 'attachment' === $post->post_type ) {
			$_wp_attached_file = get_post_meta( $id, '_wp_attached_file', true );
			if ( $_wp_attached_file && ( strpos( $_wp_attached_file, 'http://' ) === 0 || strpos( $_wp_attached_file, 'https://' ) === 0 ) ) {
				$url = $_wp_attached_file;
			}
		}
		return $url;
	}

	/**
	 * @param $sources
	 * @param $size_array
	 * @param $image_src
	 * @param $image_meta
	 * @param $attachment_id
	 *
	 * @return mixed
	 */
	public function pinkbridge_ptc_dam_wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! get_post_meta( $attachment_id, '_ptc_dam_external_url', true ) ) {
			return $sources;
		}
		if ( $sources ) {
			$upload_dir    = wp_get_upload_dir();
			$image_baseurl = trailingslashit( $upload_dir['baseurl'] );
			if ( is_ssl() && 'https' !== substr( $image_baseurl, 0, 5 ) && ! empty( $_SERVER['HTTP_HOST'] ) && wp_parse_url( $image_baseurl, PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] ) {
				$image_baseurl = set_url_scheme( $image_baseurl, 'https' );
			}
			$_wp_attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
			foreach ( $sources as &$src ) {
				$pos = strpos( $_wp_attached_file, 'wp-content/uploads/' );
				if ( false !== $pos ) {
					$src['url'] = str_replace( $image_baseurl, substr( $_wp_attached_file, 0, $pos - 1 ) . '/wp-content/uploads/', $src['url'] );
				} else {
					$src['url'] = str_replace( $image_baseurl, '', $src['url'] );
				}
			}
		}
		return $sources;
	}

	/**
	 * Skip if the image src is external
	 *
	 * @param $skip_image
	 * @param $src
	 * @param $tag
	 *
	 * @return mixed
	 */
	public function pinkbridge_ptc_dam_jetpack_photon_skip_image( $skip_image, $src, $tag ) {
		if ( ! $skip_image && strpos( $src, get_site_url() ) !== 0 ) {
			$skip_image = true;
		}
		return $skip_image;
	}

	/**
	 * Add needed post meta when an external image is cloned by WPML
	 *
	 * @param $attachment_id
	 * @param $duplicated_attachment_id
	 */
	public function pinkbridge_ptc_dam_wpml_after_duplicate_attachment( $attachment_id, $duplicated_attachment_id ) {
		$_ptc_dam_external_url = get_post_meta( $attachment_id, '_ptc_dam_external_url', true );
		if ( $_ptc_dam_external_url ) {
			update_post_meta( $duplicated_attachment_id, '_ptc_dam_external_url', $_ptc_dam_external_url );
		}
	}
	
	/**
	 * Add needed import product url
	 *
	 * @param $attachment_id
	 * @param $duplicated_attachment_id
	 */
	public function pinkbridge_ptc_dam_search_exmage_url_when_import_product( &$q ) {
		if ( empty( $q->query_vars['meta_query'] ) ) {
			return;
		}
		$file = '';
		foreach ( $q->query_vars['meta_query'] as $mt_qr ) {
			if ( ! empty( $mt_qr['key'] ) && $mt_qr['key'] == '_wc_attachment_source' ) {
				$file = $mt_qr['value'];
				break;
			}
		}
		if ( ! $file ) {
			return;
		}
		$q->query_vars['meta_query'][] = [
			'key'     => '_ptc_dam_external_url',
			'value'   => $file,
			'compare' => 'LIKE',
		];
		$q->query_vars['meta_query']['relation'] = 'OR';
		
	}
	
}

/**
 * Get pinkbridge DAM Options.
 *
 * @access public
 * @return array
 * @since 2.0.0
 */
function pinkbridge_dam_get_options( $key = null ) {
	$pinkbridge_options = get_option( PINKBRIDGE_DAM_OPTIONS, array() );
	if ( $key ) {
		$pinkbridge_options = isset( $pinkbridge_options[ $key ] ) ? $pinkbridge_options[ $key ] : '';
	}
	return $pinkbridge_options;
}

/**
 * Update pinkbridge DAM Options.
 *
 * @access public
 * @since 2.0.0
 */
function pinkbridge_dam_update_options( $pinkbridge_options ) {
	update_option( PINKBRIDGE_DAM_OPTIONS, $pinkbridge_options );
}

/**
 * Get Pinkbridge token Keys
 *
 * @return array Pinkbridge token Keys
 */
function pinkbridge_dam_get_tokenkeys() {
	$ptc_access_token  = array();
	$pinkbridge_options = pinkbridge_dam_get_options();
	if ( isset( $pinkbridge_options['ptc_access_token'] ) && ! empty( $pinkbridge_options['ptc_access_token'] ) ) {
		$ptc_access_token['ptc_access_token'] = $pinkbridge_options['ptc_access_token'];
	}
	if ( isset( $pinkbridge_options['ptc_refresh_token'] ) && ! empty( $pinkbridge_options['ptc_refresh_token'] ) ) {
		$ptc_access_token['ptc_refresh_token'] = $pinkbridge_options['ptc_refresh_token'];
	}

	return $ptc_access_token;
}

/**
 * Check if Access token is valid or not.
 *
 * @return bool
 */
function pinkbridge_dam_is_access_token_valid() {
	$pinkbridge_options = pinkbridge_dam_get_options();
	$token_status   = isset( $pinkbridge_options['token_status'] ) ? esc_attr( $pinkbridge_options['token_status'] ) : '';
	if ( 'valid' === $token_status ) {
		return true;
	}
	return false;
}