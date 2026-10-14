<?php
/**
 * GitHub-backed WordPress update integration.
 *
 * @package ShufflesYoutubeShorts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSYS_Updater {
	const METADATA_URL  = 'https://raw.githubusercontent.com/Shuffles66/shuffles-youtube-shorts/main/update.json';
	const CHANGELOG_URL = 'https://raw.githubusercontent.com/Shuffles66/shuffles-youtube-shorts/main/CHANGELOG.md';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'normalize_package_directory' ), 10, 4 );
	}

	public static function check_for_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$metadata = self::get_metadata();
		if ( ! $metadata || empty( $metadata['version'] ) || ! version_compare( $metadata['version'], SSYS_VERSION, '>' ) ) {
			return $transient;
		}

		$plugin_file = plugin_basename( SSYS_FILE );
		$update      = (object) array(
			'id'           => 'github.com/' . SSYS_GITHUB_REPOSITORY,
			'slug'         => 'shuffles-youtube-shorts',
			'plugin'       => $plugin_file,
			'new_version'  => sanitize_text_field( $metadata['version'] ),
			'url'          => 'https://github.com/' . SSYS_GITHUB_REPOSITORY,
			'package'      => self::package_url( $metadata ),
			'tested'       => isset( $metadata['tested'] ) ? sanitize_text_field( $metadata['tested'] ) : '',
			'requires'     => isset( $metadata['requires'] ) ? sanitize_text_field( $metadata['requires'] ) : '6.2',
			'requires_php' => isset( $metadata['requires_php'] ) ? sanitize_text_field( $metadata['requires_php'] ) : '7.4',
			'icons'        => array(),
			'banners'      => array(),
		);

		$transient->response[ $plugin_file ] = $update;
		return $transient;
	}

	public static function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'shuffles-youtube-shorts' !== $args->slug ) {
			return $result;
		}

		$metadata = self::get_metadata();
		if ( ! $metadata ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Shuffles Youtube Shorts',
			'slug'          => 'shuffles-youtube-shorts',
			'version'       => sanitize_text_field( $metadata['version'] ),
			'author'        => '<a href="https://github.com/Shuffles66">Shuffles</a>',
			'homepage'      => 'https://github.com/' . SSYS_GITHUB_REPOSITORY,
			'requires'      => isset( $metadata['requires'] ) ? sanitize_text_field( $metadata['requires'] ) : '6.2',
			'requires_php'  => isset( $metadata['requires_php'] ) ? sanitize_text_field( $metadata['requires_php'] ) : '7.4',
			'tested'        => isset( $metadata['tested'] ) ? sanitize_text_field( $metadata['tested'] ) : '',
			'download_link' => self::package_url( $metadata ),
			'sections'      => array(
				'description' => '<p>Responsive YouTube Shorts galleries that automatically refresh from a channel or playlist.</p>',
				'changelog'   => self::get_changelog_html(),
			),
		);
	}

	public static function normalize_package_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || plugin_basename( SSYS_FILE ) !== $hook_extra['plugin'] ) {
			return $source;
		}

		$source_name = basename( untrailingslashit( $source ) );
		if ( 'shuffles-youtube-shorts' === $source_name ) {
			return $source;
		}

		global $wp_filesystem;
		$corrected = trailingslashit( $remote_source ) . 'shuffles-youtube-shorts/';
		if ( $wp_filesystem && $wp_filesystem->move( $source, $corrected, true ) ) {
			return $corrected;
		}

		return new WP_Error( 'ssys_update_directory', __( 'The Shuffles Youtube Shorts update package could not be prepared.', 'shuffles-youtube-shorts' ) );
	}

	public static function get_changelog_html() {
		$cache_key = 'ssys_github_changelog';
		$markdown  = get_transient( $cache_key );

		if ( false === $markdown ) {
			$response = wp_remote_get(
				self::CHANGELOG_URL,
				array(
					'timeout'    => 8,
					'user-agent' => 'Shuffles-Youtube-Shorts/' . SSYS_VERSION,
				)
			);
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$markdown = wp_remote_retrieve_body( $response );
				set_transient( $cache_key, $markdown, 6 * HOUR_IN_SECONDS );
			} else {
				$local_file = SSYS_DIR . 'CHANGELOG.md';
				$markdown   = file_exists( $local_file ) ? file_get_contents( $local_file ) : '## 1.0.0'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}

		return self::format_markdown( $markdown );
	}

	private static function get_metadata() {
		$cache_key = 'ssys_github_update_metadata';
		$metadata  = get_site_transient( $cache_key );
		if ( false !== $metadata ) {
			return is_array( $metadata ) ? $metadata : false;
		}

		$response = wp_remote_get(
			self::METADATA_URL,
			array(
				'timeout'    => 8,
				'user-agent' => 'Shuffles-Youtube-Shorts/' . SSYS_VERSION,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( $cache_key, 'unavailable', HOUR_IN_SECONDS );
			return false;
		}

		$metadata = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $metadata ) || empty( $metadata['version'] ) || ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $metadata['version'] ) ) {
			return false;
		}

		set_site_transient( $cache_key, $metadata, 6 * HOUR_IN_SECONDS );
		return $metadata;
	}

	private static function package_url( $metadata ) {
		$version = rawurlencode( sanitize_text_field( $metadata['version'] ) );
		return 'https://github.com/' . SSYS_GITHUB_REPOSITORY . '/releases/download/v' . $version . '/shuffles-youtube-shorts.zip';
	}

	private static function format_markdown( $markdown ) {
		$lines   = preg_split( '/\R/', (string) $markdown );
		$html    = '';
		$in_list = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^#{1,3}\s+(.+)$/', $line, $matches ) ) {
				if ( $in_list ) {
					$html   .= '</ul>';
					$in_list = false;
				}
				$html .= '<h3>' . esc_html( $matches[1] ) . '</h3>';
			} elseif ( preg_match( '/^[-*]\s+(.+)$/', $line, $matches ) ) {
				if ( ! $in_list ) {
					$html   .= '<ul>';
					$in_list = true;
				}
				$html .= '<li>' . esc_html( $matches[1] ) . '</li>';
			} elseif ( '' !== $line ) {
				if ( $in_list ) {
					$html   .= '</ul>';
					$in_list = false;
				}
				$html .= '<p>' . esc_html( $line ) . '</p>';
			}
		}

		if ( $in_list ) {
			$html .= '</ul>';
		}

		return $html;
	}
}

