<?php
/**
 * YouTube Data API v3 client.
 *
 * @package ShufflesYoutubeShorts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSYS_API {
	const API_BASE = 'https://www.googleapis.com/youtube/v3/';

	public static function get_shorts( $api_key, $channel, $playlist, $limit, $cache_minutes ) {
		$limit         = max( 1, min( 60, absint( $limit ) ) );
		$cache_minutes = max( 1, min( 1440, absint( $cache_minutes ) ) );
		$cache_salt    = (string) get_option( 'ssys_cache_salt', '1' );
		$cache_key     = 'ssys_' . md5( $cache_salt . '|' . md5( $api_key ) . '|' . $channel . '|' . $playlist . '|' . $limit );
		$cached        = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$source_title = '';
		$playlist_id  = self::parse_playlist_id( $playlist );
		if ( ! empty( $playlist ) && empty( $playlist_id ) ) {
			return new WP_Error( 'ssys_playlist_invalid', __( 'The playlist value is not a valid YouTube playlist ID or URL.', 'shuffles-youtube-shorts' ) );
		}

		if ( ! self::is_valid_api_key( $api_key ) ) {
			$result = self::get_public_shorts( $channel, $playlist_id, $limit );
			if ( ! is_wp_error( $result ) ) {
				set_transient( $cache_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
			}
			return $result;
		}

		if ( empty( $playlist_id ) ) {
			$channel_data = self::resolve_channel( $api_key, $channel );
			if ( is_wp_error( $channel_data ) ) {
				return self::public_fallback( $channel_data, $channel, $playlist_id, $limit, $cache_key, $cache_minutes );
			}
			$playlist_id  = $channel_data['uploads'];
			$source_title = $channel_data['title'];
		} else {
			$playlist_data = self::request(
				'playlists',
				array(
					'part' => 'snippet',
					'id'   => $playlist_id,
				),
				$api_key
			);
			if ( is_wp_error( $playlist_data ) ) {
				return self::public_fallback( $playlist_data, $channel, $playlist_id, $limit, $cache_key, $cache_minutes );
			}
			if ( empty( $playlist_data['items'][0] ) ) {
				return self::public_fallback( new WP_Error( 'ssys_playlist_not_found', __( 'The YouTube playlist could not be found or is not public.', 'shuffles-youtube-shorts' ) ), $channel, $playlist_id, $limit, $cache_key, $cache_minutes );
			}
			$source_title = sanitize_text_field( $playlist_data['items'][0]['snippet']['title'] );
		}

		$videos         = array();
		$next_page      = '';
		$pages_checked  = 0;
		$maximum_pages  = 5;

		do {
			$params = array(
				'part'       => 'contentDetails,snippet',
				'playlistId' => $playlist_id,
				'maxResults' => 50,
			);
			if ( $next_page ) {
				$params['pageToken'] = $next_page;
			}

			$playlist_items = self::request( 'playlistItems', $params, $api_key );
			if ( is_wp_error( $playlist_items ) ) {
				return self::public_fallback( $playlist_items, $channel, $playlist_id, $limit, $cache_key, $cache_minutes );
			}

			$ordered_ids = array();
			foreach ( isset( $playlist_items['items'] ) ? $playlist_items['items'] : array() as $item ) {
				$id = isset( $item['contentDetails']['videoId'] ) ? sanitize_text_field( $item['contentDetails']['videoId'] ) : '';
				if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ) {
					$ordered_ids[] = $id;
				}
			}

			if ( $ordered_ids ) {
				$details = self::request(
					'videos',
					array(
						'part' => 'snippet,contentDetails,status',
						'id'   => implode( ',', $ordered_ids ),
					),
					$api_key
				);
				if ( is_wp_error( $details ) ) {
					return self::public_fallback( $details, $channel, $playlist_id, $limit, $cache_key, $cache_minutes );
				}

				$by_id = array();
				foreach ( isset( $details['items'] ) ? $details['items'] : array() as $item ) {
					$by_id[ $item['id'] ] = $item;
				}

				foreach ( $ordered_ids as $id ) {
					if ( empty( $by_id[ $id ] ) ) {
						continue;
					}
					$item       = $by_id[ $id ];
					$status     = isset( $item['status'] ) ? $item['status'] : array();
					$duration   = isset( $item['contentDetails']['duration'] ) ? self::duration_seconds( $item['contentDetails']['duration'] ) : 0;
					$embeddable = ! isset( $status['embeddable'] ) || (bool) $status['embeddable'];
					$public     = ! isset( $status['privacyStatus'] ) || 'public' === $status['privacyStatus'];

					if ( $duration < 1 || $duration > 180 || ! $embeddable || ! $public ) {
						continue;
					}

					$videos[] = array(
						'id'        => $id,
						'title'     => sanitize_text_field( $item['snippet']['title'] ),
						'published' => sanitize_text_field( $item['snippet']['publishedAt'] ),
						'duration'  => $duration,
					);

					if ( count( $videos ) >= $limit ) {
						break 2;
					}
				}
			}

			$next_page = isset( $playlist_items['nextPageToken'] ) ? sanitize_text_field( $playlist_items['nextPageToken'] ) : '';
			++$pages_checked;
		} while ( $next_page && $pages_checked < $maximum_pages );

		$result = array(
			'source_title' => $source_title ? $source_title : __( 'YouTube', 'shuffles-youtube-shorts' ),
			'videos'       => array_slice( $videos, 0, $limit ),
			'method'       => 'api',
		);

		set_transient( $cache_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
		return $result;
	}

	private static function public_fallback( $original_error, $channel, $playlist_id, $limit, $cache_key, $cache_minutes ) {
		$result = self::get_public_shorts( $channel, $playlist_id, $limit );
		if ( is_wp_error( $result ) ) {
			return $original_error;
		}

		set_transient( $cache_key, $result, $cache_minutes * MINUTE_IN_SECONDS );
		return $result;
	}

	public static function is_valid_api_key( $api_key ) {
		return (bool) preg_match( '/^AIza[A-Za-z0-9_-]{35}$/', trim( (string) $api_key ) );
	}

	private static function get_public_shorts( $channel, $playlist_id, $limit ) {
		if ( $playlist_id ) {
			$url = 'https://www.youtube.com/playlist?list=' . rawurlencode( $playlist_id );
		} else {
			$channel_url = self::public_channel_url( $channel );
			if ( is_wp_error( $channel_url ) ) {
				return $channel_url;
			}
			$url = trailingslashit( $channel_url ) . 'shorts';
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 4,
				'user-agent'  => 'Mozilla/5.0 (compatible; Shuffles-Youtube-Shorts/' . SSYS_VERSION . '; +' . home_url( '/' ) . ')',
				'headers'     => array( 'Accept-Language' => 'en-AU,en;q=0.9' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssys_public_request_failed', __( 'YouTube could not be reached. Please try again shortly.', 'shuffles-youtube-shorts' ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$html   = wp_remote_retrieve_body( $response );
		if ( $status < 200 || $status >= 300 || empty( $html ) ) {
			return new WP_Error( 'ssys_public_response_failed', __( 'The public YouTube feed could not be loaded.', 'shuffles-youtube-shorts' ) );
		}

		$source_title = __( 'YouTube', 'shuffles-youtube-shorts' );
		if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $title_match ) || preg_match( '/<title>([^<]+)<\/title>/i', $html, $title_match ) ) {
			$source_title = sanitize_text_field( html_entity_decode( $title_match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			$source_title = preg_replace( '/\s*-\s*YouTube\s*$/i', '', $source_title );
		}

		preg_match_all( '/["\']videoId["\']\s*:\s*["\']([A-Za-z0-9_-]{11})["\']/', $html, $matches );
		$video_ids = array_values( array_unique( isset( $matches[1] ) ? $matches[1] : array() ) );
		if ( empty( $video_ids ) ) {
			return new WP_Error( 'ssys_no_public_shorts', __( 'No public Shorts are available right now.', 'shuffles-youtube-shorts' ) );
		}

		$videos = array();
		foreach ( array_slice( $video_ids, 0, $limit ) as $video_id ) {
			$videos[] = array(
				'id'        => $video_id,
				'title'     => self::get_public_video_title( $video_id ),
				'published' => '',
				'duration'  => 0,
			);
		}

		return array(
			'source_title' => $source_title,
			'videos'       => $videos,
			'method'       => 'public',
		);
	}

	private static function public_channel_url( $channel ) {
		$channel = trim( (string) $channel );
		if ( empty( $channel ) ) {
			return new WP_Error( 'ssys_channel_required', __( 'Enter a YouTube channel ID, @handle, or channel URL.', 'shuffles-youtube-shorts' ) );
		}

		if ( preg_match( '/(?:youtube\.com\/channel\/)?(UC[A-Za-z0-9_-]{22})/', $channel, $matches ) ) {
			return 'https://www.youtube.com/channel/' . rawurlencode( $matches[1] );
		}

		$handle = $channel;
		if ( preg_match( '/youtube\.com\/@([^\/?#]+)/i', $channel, $matches ) ) {
			$handle = $matches[1];
		}
		$handle = ltrim( preg_replace( '/[\/?#].*$/', '', $handle ), '@' );
		if ( ! preg_match( '/^[A-Za-z0-9._-]{3,100}$/', $handle ) ) {
			return new WP_Error( 'ssys_channel_invalid', __( 'The YouTube channel value is not valid.', 'shuffles-youtube-shorts' ) );
		}

		return 'https://www.youtube.com/@' . rawurlencode( $handle );
	}

	private static function get_public_video_title( $video_id ) {
		$url      = add_query_arg(
			array(
				'url'    => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
				'format' => 'json',
			),
			'https://www.youtube.com/oembed'
		);
		$response = wp_remote_get( $url, array( 'timeout' => 8, 'redirection' => 3 ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['title'] ) ) {
				return sanitize_text_field( $data['title'] );
			}
		}

		return __( 'YouTube Short', 'shuffles-youtube-shorts' );
	}

	private static function resolve_channel( $api_key, $channel ) {
		$channel = trim( (string) $channel );
		if ( empty( $channel ) ) {
			return new WP_Error( 'ssys_channel_required', __( 'Enter a YouTube channel ID, @handle, or channel URL.', 'shuffles-youtube-shorts' ) );
		}

		$params = array( 'part' => 'snippet,contentDetails' );
		if ( preg_match( '/(?:youtube\.com\/channel\/)?(UC[A-Za-z0-9_-]{22})/', $channel, $matches ) ) {
			$params['id'] = $matches[1];
		} else {
			$handle = $channel;
			if ( preg_match( '/youtube\.com\/@([^\/?#]+)/i', $channel, $matches ) ) {
				$handle = $matches[1];
			}
			$handle              = ltrim( preg_replace( '/[\?#].*$/', '', $handle ), '@' );
			$params['forHandle'] = sanitize_text_field( $handle );
		}

		$data = self::request( 'channels', $params, $api_key );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
			return new WP_Error( 'ssys_channel_not_found', __( 'The YouTube channel could not be found. Use its UC… channel ID, @handle, or full channel URL.', 'shuffles-youtube-shorts' ) );
		}

		return array(
			'id'      => sanitize_text_field( $data['items'][0]['id'] ),
			'title'   => sanitize_text_field( $data['items'][0]['snippet']['title'] ),
			'uploads' => sanitize_text_field( $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ),
		);
	}

	private static function parse_playlist_id( $playlist ) {
		$playlist = trim( (string) $playlist );
		if ( empty( $playlist ) ) {
			return '';
		}
		if ( preg_match( '/^[A-Za-z0-9_-]{10,50}$/', $playlist ) ) {
			return $playlist;
		}
		$query = wp_parse_url( $playlist, PHP_URL_QUERY );
		if ( $query ) {
			parse_str( $query, $values );
			if ( ! empty( $values['list'] ) && preg_match( '/^[A-Za-z0-9_-]{10,50}$/', $values['list'] ) ) {
				return $values['list'];
			}
		}
		return '';
	}

	private static function request( $endpoint, $params, $api_key ) {
		$params['key'] = $api_key;
		$url           = add_query_arg( $params, self::API_BASE . $endpoint );
		$response      = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
				'user-agent'  => 'Shuffles-Youtube-Shorts/' . SSYS_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ssys_request_failed', __( 'YouTube could not be reached. Please try again shortly.', 'shuffles-youtube-shorts' ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			$message = isset( $data['error']['message'] ) ? sanitize_text_field( $data['error']['message'] ) : __( 'YouTube returned an unexpected response.', 'shuffles-youtube-shorts' );
			return new WP_Error( 'ssys_youtube_error', $message );
		}

		return $data;
	}

	private static function duration_seconds( $duration ) {
		if ( ! preg_match( '/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', (string) $duration, $matches ) ) {
			return 0;
		}
		$days    = isset( $matches[1] ) ? (int) $matches[1] : 0;
		$hours   = isset( $matches[2] ) ? (int) $matches[2] : 0;
		$minutes = isset( $matches[3] ) ? (int) $matches[3] : 0;
		$seconds = isset( $matches[4] ) ? (int) $matches[4] : 0;
		return ( $days * DAY_IN_SECONDS ) + ( $hours * HOUR_IN_SECONDS ) + ( $minutes * MINUTE_IN_SECONDS ) + $seconds;
	}
}

