<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Pinkbridge_Dam_i18n {
	public function pinkbridge_load_plugin_textdomain() {
		load_plugin_textdomain(
			'pinkbridge-dam',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
		new Pinkbridge_Dam_Ajax();
	}
}
