<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Pinkbridge_Dam_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pinkbridge_enqueue_styles($hook) {
		if($hook == 'pinkbridge_page_pinkbridge-formular'){
			wp_enqueue_style( 'pinkbridge-dam-admin.css', plugin_dir_url( __FILE__ ) . 'css/pinkbridge-dam-admin.css', array(), $this->version, 'all' );
		}
		wp_enqueue_style( 'pinkbridge-dam-media.css', plugin_dir_url( __FILE__ ) . 'css/pinkbridge-dam-media.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pinkbridge_enqueue_scripts($hook) {
		if($hook == 'pinkbridge_page_pinkbridge-formular'){
			wp_enqueue_script( 'pinkbridge-dam-admin.js', plugin_dir_url( __FILE__ ) . 'js/pinkbridge-dam-admin.js', array( 'jquery' ), $this->version, false );
		}
		
		// Create a nonce to pass to the JavaScript file
		$ptc_dam_media_ajax_nonce = wp_create_nonce( 'ptc_dam_media_ajax_nonce' );
		$nonce_action = 'ptc_dam_media_ajax_nonce';
		$admin_page_url = menu_page_url('pinkbridge-formular', false);
		
		wp_enqueue_script('ptc-dam-media-js', plugin_dir_url( __FILE__ ) . 'js/pinkbridge-dam-media.js', array('jquery'), $this->version, false);
		wp_localize_script('ptc-dam-media-js', 'customMedia', array(
			'ajaxurl' => esc_url(admin_url('admin-ajax.php')),
			'tabName' => esc_html__('DAM', 'pinkbridge-dam'),
			'nonce'    => esc_attr($ptc_dam_media_ajax_nonce)
		));

		wp_enqueue_script('ptc-dam-common-media-js', plugin_dir_url( __FILE__ ) . 'js/pinkbridge-media.js', array('jquery'), $this->version, false);
		wp_localize_script('ptc-dam-common-media-js', 'customMedia', array(
			'ajaxurl' => esc_url(admin_url('admin-ajax.php')),
			'nonce' => esc_attr($ptc_dam_media_ajax_nonce),
			'nonce_action' => esc_attr($nonce_action),
			'admin_page_url' => esc_url($admin_page_url)
		));
	}

	/**
	 * Add admin menu page in backend area
	 * Called Pinktree Bridge
	 */
	public function pinkbridge_add_menu_page() {
		$menu_slug = 'pinkbridge-dam'; 
		add_menu_page(
			esc_html__('PinkBridge', 'pinkbridge-dam'), 
			esc_html__('PinkBridge', 'pinkbridge-dam'), 
			'manage_options', 
			esc_attr($menu_slug),
			array($this, 'pinkbridge_menuPage'), 
			'dashicons-forms'
		);

		add_submenu_page(
			esc_attr($menu_slug),
			esc_html__('Formular', 'pinkbridge-dam'),
			esc_html__('Formular', 'pinkbridge-dam'),
			'manage_options',
			'pinkbridge-formular',
			array($this, 'pinkbridge_menuPage')
		);
		unset($GLOBALS['submenu']['pinkbridge-dam'][0]);
    }
	
	/**
	 * Include file for the module
	 */
	public static function pinkbridge_menuPage() {
        if ( is_file( PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-admin-display.php' ) ) {
            include_once PINKBRIDGE_DAM_PLUGIN_DIR . 'admin/partials/pinkbridge-dam-admin-display.php';
        }
    }

	/**Add link to Documentation, Support and Reviews
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	function pinkbridge_ptc_dam_plugin_row_meta( $links, $file ) {
		if ( PINKBRIDGE_DAM_PLUGIN_NAME !== $file ) {
			return $links;
		}
		
		// The Pinkbridge documentation URL.
		$docs_url = apply_filters( 'pinkbridge_ptc_dam_docs_url', 'https://pinkbridge.de/de/kontakt' );

		// The Pinkbridge support URL.
		$support_url = apply_filters( 'pinkbridge_ptc_dam_support_url', 'https://pinkbridge.de/de/impressum' );

		$row_meta = array(
			'docs'    => '<a href="' . esc_url( $docs_url ) . '" target="_blank" title="' . esc_attr__( 'View PinkBridge Documentation', 'pinkbridge-dam' ) . '">' . esc_html__( 'Docs', 'pinkbridge-dam' ) . '</a>',
			'support' => '<a href="' . esc_url( $support_url ) . '" target="_blank" title="' . esc_attr__( 'Visit Pinkbridge Support', 'pinkbridge-dam' ) . '">' . esc_html__( 'Support', 'pinkbridge-dam' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}
}
