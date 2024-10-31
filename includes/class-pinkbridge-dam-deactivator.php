<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Pinkbridge_Dam_Deactivator {
	public static function deactivate() {
		global $wpdb;

		delete_option(PINKBRIDGE_DAM_OPTIONS);
		
		remove_shortcode( 'pinkbridge_dam_formular' );
		
		// @codingStandardsIgnoreStart
		$attachment_ids = $wpdb->get_col(
			"SELECT post_id
			FROM {$wpdb->prefix}postmeta
			WHERE meta_key IN ('_ptc_dam_external_url', '_ptc_dam_img_id') GROUP BY post_id"
		);
		// @codingStandardsIgnoreEnd
		
		if(!empty($attachment_ids)){
			$attachment_ids_placeholders = implode(', ', array_fill(0, count($attachment_ids), '%d'));
			// @codingStandardsIgnoreStart
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}postmeta
					WHERE post_id IN ($attachment_ids_placeholders)",
					...$attachment_ids
				)
			);
			
			$wpdb->query(
				$wpdb->prepare(
					"DELETE p
					FROM {$wpdb->prefix}posts p
					WHERE p.post_type = 'attachment' AND p.ID IN ($attachment_ids_placeholders)",
					...$attachment_ids
				)
			);
			
			// @codingStandardsIgnoreEnd
		}

		// @codingStandardsIgnoreStart
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
		// @codingStandardsIgnoreEnd

		wp_cache_flush();
	}
}
