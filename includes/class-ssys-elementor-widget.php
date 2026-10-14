<?php
/**
 * Elementor widget integration.
 *
 * @package ShufflesYoutubeShorts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSYS_Elementor_Widget extends \Elementor\Widget_Base {
	public function get_name(): string {
		return 'shuffles-youtube-shorts';
	}

	public function get_title(): string {
		return __( 'Shuffles YouTube Shorts', 'shuffles-youtube-shorts' );
	}

	public function get_icon(): string {
		return 'eicon-youtube';
	}

	public function get_categories(): array {
		return array( 'shuffles', 'general' );
	}

	public function get_keywords(): array {
		return array( 'youtube', 'shorts', 'video', 'gallery', 'shuffles' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array( 'label' => __( 'Gallery', 'shuffles-youtube-shorts' ) )
		);

		$this->add_control(
			'channel',
			array(
				'label'       => __( 'Channel', 'shuffles-youtube-shorts' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '@ByronRides or UC…',
				'description' => __( 'Leave blank to use the plugin default.', 'shuffles-youtube-shorts' ),
			)
		);
		$this->add_control(
			'playlist',
			array(
				'label'       => __( 'Shorts playlist', 'shuffles-youtube-shorts' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'PL…',
				'description' => __( 'Optional. Takes priority over the channel.', 'shuffles-youtube-shorts' ),
			)
		);
		$this->add_control(
			'cols',
			array(
				'label'   => __( 'Desktop columns', 'shuffles-youtube-shorts' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 4,
			)
		);
		$this->add_control(
			'rows',
			array(
				'label'   => __( 'Rows', 'shuffles-youtube-shorts' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 10,
				'default' => 2,
			)
		);
		$this->add_control(
			'show_titles',
			array(
				'label'        => __( 'Show titles', 'shuffles-youtube-shorts' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'shuffles-youtube-shorts' ),
				'label_off'    => __( 'No', 'shuffles-youtube-shorts' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings        = $this->get_settings_for_display();
		$plugin_settings = SSYS_Plugin::get_settings();
		$channel         = isset( $settings['channel'] ) ? trim( (string) $settings['channel'] ) : '';
		$playlist        = isset( $settings['playlist'] ) ? trim( (string) $settings['playlist'] ) : '';
		if ( '' === $channel && '' === $playlist ) {
			$channel  = $plugin_settings['channel'];
			$playlist = $plugin_settings['playlist'];
		}
		$atts = array(
			'channel'     => $channel,
			'playlist'    => $playlist,
			'cols'        => isset( $settings['cols'] ) ? $settings['cols'] : 4,
			'rows'        => isset( $settings['rows'] ) ? $settings['rows'] : 2,
			'show_titles' => isset( $settings['show_titles'] ) ? $settings['show_titles'] : 'yes',
		);

		// The renderer escapes every dynamic value and intentionally returns iframe markup.
		echo SSYS_Plugin::instance()->render_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

