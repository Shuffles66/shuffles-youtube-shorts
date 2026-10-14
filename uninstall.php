<?php
/**
 * Remove plugin data, including the stored API key.
 *
 * @package ShufflesYoutubeShorts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ssys_settings' );
delete_option( 'ssys_cache_salt' );

global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_ssys_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_ssys_' ) . '%'
	)
);

