<?php
/**
 * Plugin Name: Shuffles Youtube Shorts
 * Plugin URI:  https://github.com/Shuffles66/shuffles-youtube-shorts
 * Description: Displays automatically refreshed, responsive YouTube Shorts galleries from a channel or playlist, with server-side API key storage, a configurable shortcode, and an Elementor widget.
 * Version:     1.0.2
 * Author:      Shuffles
 * Text Domain: shuffles-youtube-shorts
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSYS_VERSION', '1.0.2' );
define( 'SSYS_FILE', __FILE__ );
define( 'SSYS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSYS_URL', plugin_dir_url( __FILE__ ) );
define( 'SSYS_GITHUB_REPOSITORY', 'Shuffles66/shuffles-youtube-shorts' );

require_once SSYS_DIR . 'includes/class-ssys-api.php';
require_once SSYS_DIR . 'includes/class-ssys-updater.php';

final class SSYS_Plugin {
	private static $instance = null;
	private $gallery_count   = 0;
	private $elementor_widget_registered = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_ajax_ssys_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'update_option_ssys_settings', array( $this, 'bust_cache' ), 10, 2 );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_elementor_widget' ) );
		add_shortcode( 'shuffles_youtube_shorts', array( $this, 'render_shortcode' ) );
		SSYS_Updater::init();
	}

	public static function activate() {
		if ( false === get_option( 'ssys_settings', false ) ) {
			add_option( 'ssys_settings', self::defaults(), '', false );
		}
		if ( false === get_option( 'ssys_cache_salt', false ) ) {
			add_option( 'ssys_cache_salt', 1, '', false );
		}
	}

	public static function defaults() {
		return array(
			'api_key'       => '',
			'channel'       => '',
			'playlist'      => '',
			'cols'          => 4,
			'rows'          => 2,
			'cache_minutes' => 15,
			'show_titles'   => 1,
		);
	}

	public static function get_settings() {
		return wp_parse_args( get_option( 'ssys_settings', array() ), self::defaults() );
	}

	public function register_settings() {
		register_setting(
			'ssys_settings_group',
			'ssys_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	public function sanitize_settings( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'api_key'       => isset( $input['api_key'] ) ? trim( sanitize_text_field( wp_unslash( $input['api_key'] ) ) ) : '',
			'channel'       => isset( $input['channel'] ) ? trim( sanitize_text_field( wp_unslash( $input['channel'] ) ) ) : '',
			'playlist'      => isset( $input['playlist'] ) ? trim( sanitize_text_field( wp_unslash( $input['playlist'] ) ) ) : '',
			'cols'          => isset( $input['cols'] ) ? max( 1, min( 6, absint( $input['cols'] ) ) ) : $defaults['cols'],
			'rows'          => isset( $input['rows'] ) ? max( 1, min( 10, absint( $input['rows'] ) ) ) : $defaults['rows'],
			'cache_minutes' => isset( $input['cache_minutes'] ) ? max( 5, min( 1440, absint( $input['cache_minutes'] ) ) ) : $defaults['cache_minutes'],
			'show_titles'   => empty( $input['show_titles'] ) ? 0 : 1,
		);
	}

	public function bust_cache( $old_value, $value ) {
		if ( $old_value !== $value ) {
			update_option( 'ssys_cache_salt', time(), false );
		}
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Shuffles Youtube Shorts', 'shuffles-youtube-shorts' ),
			__( 'YouTube Shorts', 'shuffles-youtube-shorts' ),
			'manage_options',
			'shuffles-youtube-shorts',
			array( $this, 'render_admin_page' ),
			SSYS_URL . 'assets/icon.svg',
			58
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_shuffles-youtube-shorts' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'ssys-admin', SSYS_URL . 'assets/admin.css', array(), SSYS_VERSION );
		wp_enqueue_script( 'ssys-admin', SSYS_URL . 'assets/admin.js', array(), SSYS_VERSION, true );
		wp_localize_script(
			'ssys-admin',
			'ssysAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ssys_test_connection' ),
				'testing' => __( 'Testing…', 'shuffles-youtube-shorts' ),
				'copy'    => __( 'Copy shortcode', 'shuffles-youtube-shorts' ),
				'copied'  => __( 'Copied', 'shuffles-youtube-shorts' ),
			)
		);
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'ssys-gallery', SSYS_URL . 'assets/frontend.css', array(), SSYS_VERSION );
		wp_enqueue_script( 'ssys-gallery-player', SSYS_URL . 'assets/frontend.js', array(), SSYS_VERSION, true );
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings       = self::get_settings();
		$key_configured = SSYS_API::is_valid_api_key( $settings['api_key'] );
		?>
		<div class="wrap ssys-admin-wrap">
			<section class="ssys-hero">
				<div class="ssys-brand-lockup">
					<img class="ssys-brand-icon" src="<?php echo esc_url( SSYS_URL . 'assets/icon.svg' ); ?>" alt="" width="72" height="72" />
					<div>
					<p class="ssys-eyebrow"><?php esc_html_e( 'Shuffles video tools', 'shuffles-youtube-shorts' ); ?></p>
					<h1><?php esc_html_e( 'YouTube Shorts', 'shuffles-youtube-shorts' ); ?></h1>
					<p><?php esc_html_e( 'Build fast, responsive vertical-video galleries that automatically refresh from a YouTube channel or playlist.', 'shuffles-youtube-shorts' ); ?></p>
					</div>
				</div>
				<span class="ssys-status <?php echo $key_configured ? 'is-ready' : 'needs-key'; ?>">
					<?php echo $key_configured ? esc_html__( 'API key configured', 'shuffles-youtube-shorts' ) : esc_html__( 'Public feed mode', 'shuffles-youtube-shorts' ); ?>
				</span>
			</section>

			<div class="ssys-admin-grid">
				<main class="ssys-panel ssys-settings-panel">
					<div class="ssys-panel-heading">
						<span class="ssys-step">1</span>
						<div>
							<h2><?php esc_html_e( 'Connect YouTube', 'shuffles-youtube-shorts' ); ?></h2>
							<p><?php esc_html_e( 'An API key is optional for channel galleries. When unavailable or invalid, the plugin automatically uses the public YouTube Shorts feed.', 'shuffles-youtube-shorts' ); ?></p>
						</div>
					</div>

					<form method="post" action="options.php" id="ssys-settings-form">
						<?php settings_fields( 'ssys_settings_group' ); ?>

						<div class="ssys-field">
							<label for="ssys-api-key"><?php esc_html_e( 'YouTube Data API key', 'shuffles-youtube-shorts' ); ?></label>
							<div class="ssys-secret-control">
								<input id="ssys-api-key" name="ssys_settings[api_key]" type="password" value="<?php echo esc_attr( $settings['api_key'] ); ?>" autocomplete="new-password" spellcheck="false" placeholder="AIza…" />
								<button type="button" class="button" id="ssys-toggle-key" aria-pressed="false"><?php esc_html_e( 'Show', 'shuffles-youtube-shorts' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'Optional. Restrict a valid key to the YouTube Data API v3. A Cloudways server-IP restriction is recommended; HTTP-referrer restrictions for this site are also supported.', 'shuffles-youtube-shorts' ); ?></p>
						</div>

						<div class="ssys-field-row">
							<div class="ssys-field">
								<label for="ssys-channel"><?php esc_html_e( 'Default channel', 'shuffles-youtube-shorts' ); ?></label>
								<input id="ssys-channel" name="ssys_settings[channel]" type="text" value="<?php echo esc_attr( $settings['channel'] ); ?>" placeholder="@ByronRides or UC…" />
								<p class="description"><?php esc_html_e( 'Use a channel ID, @handle, or YouTube channel URL.', 'shuffles-youtube-shorts' ); ?></p>
							</div>
							<div class="ssys-field">
								<label for="ssys-playlist"><?php esc_html_e( 'Default Shorts playlist (optional)', 'shuffles-youtube-shorts' ); ?></label>
								<input id="ssys-playlist" name="ssys_settings[playlist]" type="text" value="<?php echo esc_attr( $settings['playlist'] ); ?>" placeholder="PL… or playlist URL" />
								<p class="description"><?php esc_html_e( 'A curated playlist gives the most exact Shorts-only result and takes priority over the channel.', 'shuffles-youtube-shorts' ); ?></p>
							</div>
						</div>

						<div class="ssys-field-row ssys-field-row-compact">
							<div class="ssys-field">
								<label for="ssys-cols"><?php esc_html_e( 'Desktop columns', 'shuffles-youtube-shorts' ); ?></label>
								<input id="ssys-cols" name="ssys_settings[cols]" type="number" min="1" max="6" value="<?php echo esc_attr( $settings['cols'] ); ?>" />
							</div>
							<div class="ssys-field">
								<label for="ssys-rows"><?php esc_html_e( 'Rows', 'shuffles-youtube-shorts' ); ?></label>
								<input id="ssys-rows" name="ssys_settings[rows]" type="number" min="1" max="10" value="<?php echo esc_attr( $settings['rows'] ); ?>" />
							</div>
							<div class="ssys-field">
								<label for="ssys-cache"><?php esc_html_e( 'Refresh interval', 'shuffles-youtube-shorts' ); ?></label>
								<select id="ssys-cache" name="ssys_settings[cache_minutes]">
									<?php foreach ( array( 5 => '5 minutes', 15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour', 360 => '6 hours', 1440 => '24 hours' ) as $minutes => $label ) : ?>
										<option value="<?php echo esc_attr( $minutes ); ?>" <?php selected( (int) $settings['cache_minutes'], $minutes ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<label class="ssys-check">
							<input name="ssys_settings[show_titles]" type="checkbox" value="1" <?php checked( ! empty( $settings['show_titles'] ) ); ?> />
							<span><?php esc_html_e( 'Show video titles below each Short', 'shuffles-youtube-shorts' ); ?></span>
						</label>

						<div class="ssys-actions">
							<?php submit_button( __( 'Save settings', 'shuffles-youtube-shorts' ), 'primary', 'submit', false ); ?>
							<button type="button" class="button button-secondary" id="ssys-test-connection"><?php esc_html_e( 'Test connection', 'shuffles-youtube-shorts' ); ?></button>
							<span id="ssys-test-result" role="status" aria-live="polite"></span>
						</div>
					</form>
				</main>

				<aside class="ssys-stack">
					<section class="ssys-panel">
						<div class="ssys-panel-heading compact">
							<span class="ssys-step">2</span>
							<div><h2><?php esc_html_e( 'Create an API key', 'shuffles-youtube-shorts' ); ?></h2></div>
						</div>
						<ol class="ssys-instructions">
							<li><?php echo wp_kses_post( __( 'Open <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> and select or create a project.', 'shuffles-youtube-shorts' ) ); ?></li>
							<li><?php esc_html_e( 'Open APIs & Services → Library, find YouTube Data API v3, and enable it.', 'shuffles-youtube-shorts' ); ?></li>
							<li><?php esc_html_e( 'Open APIs & Services → Credentials, choose Create credentials → API key, and copy the key.', 'shuffles-youtube-shorts' ); ?></li>
							<li><?php esc_html_e( 'Edit the key and restrict its API access to YouTube Data API v3. For server-side use, prefer an IP-address restriction using your current Cloudways server IP. If using an HTTP-referrer restriction, allow this site URL with /* at the end.', 'shuffles-youtube-shorts' ); ?></li>
							<li><?php esc_html_e( 'Paste the key here, save, and test the connection.', 'shuffles-youtube-shorts' ); ?></li>
						</ol>
						<p class="ssys-note"><?php esc_html_e( 'Google applies a daily API quota. A 15–60 minute cache interval is a good balance for most sites.', 'shuffles-youtube-shorts' ); ?></p>
					</section>

					<section class="ssys-panel">
						<div class="ssys-panel-heading compact">
							<span class="ssys-step">3</span>
							<div><h2><?php esc_html_e( 'Add the gallery', 'shuffles-youtube-shorts' ); ?></h2></div>
						</div>
						<p><?php esc_html_e( 'Add this shortcode to a page, post, template, or an Elementor Shortcode widget:', 'shuffles-youtube-shorts' ); ?></p>
						<div class="ssys-code-row">
							<code id="ssys-generated-shortcode">[shuffles_youtube_shorts cols=&quot;<?php echo esc_attr( $settings['cols'] ); ?>&quot; rows=&quot;<?php echo esc_attr( $settings['rows'] ); ?>&quot;]</code>
							<button type="button" class="button" id="ssys-copy-shortcode"><?php esc_html_e( 'Copy shortcode', 'shuffles-youtube-shorts' ); ?></button>
						</div>
						<dl class="ssys-attributes">
							<div><dt>channel</dt><dd><?php esc_html_e( 'Channel ID, @handle, or URL', 'shuffles-youtube-shorts' ); ?></dd></div>
							<div><dt>playlist</dt><dd><?php esc_html_e( 'Playlist ID or URL; overrides channel', 'shuffles-youtube-shorts' ); ?></dd></div>
							<div><dt>cols</dt><dd><?php esc_html_e( 'Desktop columns, 1–6', 'shuffles-youtube-shorts' ); ?></dd></div>
							<div><dt>rows</dt><dd><?php esc_html_e( 'Rows, 1–10', 'shuffles-youtube-shorts' ); ?></dd></div>
							<div><dt>show_titles</dt><dd><?php esc_html_e( 'yes or no', 'shuffles-youtube-shorts' ); ?></dd></div>
						</dl>
						<p class="description"><?php esc_html_e( 'Elementor users can also search the widget panel for “Shuffles YouTube Shorts”.', 'shuffles-youtube-shorts' ); ?></p>
					</section>

					<section class="ssys-panel ssys-changelog-panel">
						<div class="ssys-panel-heading compact">
							<span class="ssys-step dashicons dashicons-backup" aria-hidden="true"></span>
							<div>
								<h2><?php esc_html_e( 'Updates & changelog', 'shuffles-youtube-shorts' ); ?></h2>
								<p><?php echo esc_html( sprintf( __( 'Installed version %s', 'shuffles-youtube-shorts' ), SSYS_VERSION ) ); ?></p>
							</div>
						</div>
						<p><?php esc_html_e( 'WordPress checks the public GitHub repository for version updates. Release notes are maintained in CHANGELOG.md and shown here.', 'shuffles-youtube-shorts' ); ?></p>
						<div class="ssys-changelog-content">
							<?php echo wp_kses_post( SSYS_Updater::get_changelog_html() ); ?>
						</div>
						<p><a class="button" href="<?php echo esc_url( 'https://github.com/' . SSYS_GITHUB_REPOSITORY ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View repository', 'shuffles-youtube-shorts' ); ?></a></p>
					</section>
				</aside>
			</div>
		</div>
		<?php
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'ssys_test_connection', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to test this connection.', 'shuffles-youtube-shorts' ) ), 403 );
		}

		$saved    = self::get_settings();
		$api_key  = isset( $_POST['api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) ) : $saved['api_key'];
		$channel  = isset( $_POST['channel'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['channel'] ) ) ) : $saved['channel'];
		$playlist = isset( $_POST['playlist'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['playlist'] ) ) ) : $saved['playlist'];

		if ( empty( $channel ) && empty( $playlist ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a channel or playlist first.', 'shuffles-youtube-shorts' ) ), 400 );
		}

		$result = SSYS_API::get_shorts( $api_key, $channel, $playlist, 4, 1 );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: source name, 2: number of sample Shorts found. */
					__( 'Connected to %1$s using %3$s. Found %2$d recent Short(s) in the sample.', 'shuffles-youtube-shorts' ),
					$result['source_title'],
					count( $result['videos'] ),
					isset( $result['method'] ) && 'api' === $result['method'] ? __( 'the YouTube Data API', 'shuffles-youtube-shorts' ) : __( 'the public Shorts feed', 'shuffles-youtube-shorts' )
				),
			)
		);
	}

	public function render_shortcode( $atts = array() ) {
		$settings = self::get_settings();
		$raw_atts = is_array( $atts ) ? $atts : array();
		$default_playlist = array_key_exists( 'channel', $raw_atts ) && ! array_key_exists( 'playlist', $raw_atts ) ? '' : $settings['playlist'];
		$atts     = shortcode_atts(
			array(
				'channel'     => $settings['channel'],
				'playlist'    => $default_playlist,
				'cols'        => $settings['cols'],
				'rows'        => $settings['rows'],
				'show_titles' => $settings['show_titles'] ? 'yes' : 'no',
			),
			$atts,
			'shuffles_youtube_shorts'
		);

		$cols        = max( 1, min( 6, absint( $atts['cols'] ) ) );
		$rows        = max( 1, min( 10, absint( $atts['rows'] ) ) );
		$limit       = $cols * $rows;
		$channel     = trim( sanitize_text_field( $atts['channel'] ) );
		$playlist    = trim( sanitize_text_field( $atts['playlist'] ) );
		$show_titles = ! in_array( strtolower( (string) $atts['show_titles'] ), array( '0', 'false', 'no', 'off' ), true );

		if ( empty( $channel ) && empty( $playlist ) ) {
			return $this->render_message( __( 'The YouTube gallery needs a channel or playlist.', 'shuffles-youtube-shorts' ), true );
		}

		$result = SSYS_API::get_shorts( $settings['api_key'], $channel, $playlist, $limit, (int) $settings['cache_minutes'] );
		if ( is_wp_error( $result ) ) {
			return $this->render_message( $result->get_error_message(), true );
		}
		if ( empty( $result['videos'] ) ) {
			return $this->render_message( __( 'No public Shorts are available right now.', 'shuffles-youtube-shorts' ) );
		}

		++$this->gallery_count;
		$gallery_id = 'ssys-gallery-' . $this->gallery_count;

		ob_start();
		?>
		<section id="<?php echo esc_attr( $gallery_id ); ?>" class="ssys-gallery" aria-label="<?php echo esc_attr( sprintf( __( 'YouTube Shorts from %s', 'shuffles-youtube-shorts' ), $result['source_title'] ) ); ?>">
			<div class="ssys-grid" data-cols="<?php echo esc_attr( $cols ); ?>" style="--ssys-cols: <?php echo esc_attr( $cols ); ?>;">
				<?php foreach ( $result['videos'] as $video_index => $video ) : ?>
					<article class="ssys-card">
						<div class="ssys-player">
							<?php
							$player_id = $gallery_id . '-video-' . ( $video_index + 1 );
							$embed_url = add_query_arg(
								array(
									'rel'         => 0,
									'playsinline' => 1,
									'enablejsapi' => 1,
									'origin'      => untrailingslashit( home_url( '/' ) ),
								),
								'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video['id'] )
							);
							?>
							<iframe
								id="<?php echo esc_attr( $player_id ); ?>"
								src="<?php echo esc_url( $embed_url ); ?>"
								title="<?php echo esc_attr( $video['title'] ); ?>"
								data-ssys-player
								loading="lazy"
								allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
								referrerpolicy="strict-origin-when-cross-origin"
								allowfullscreen></iframe>
						</div>
						<?php if ( $show_titles ) : ?>
							<h3 class="ssys-title"><?php echo esc_html( $video['title'] ); ?></h3>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	private function render_message( $message, $admin_detail = false ) {
		if ( $admin_detail && ! current_user_can( 'manage_options' ) ) {
			$message = __( 'Videos are temporarily unavailable.', 'shuffles-youtube-shorts' );
		}
		return '<p class="ssys-message" role="status">' . esc_html( $message ) . '</p>';
	}

	public function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'shuffles',
			array(
				'title' => __( 'Shuffles', 'shuffles-youtube-shorts' ),
				'icon'  => 'eicon-youtube',
			)
		);
	}

	public function register_elementor_widget( $widgets_manager = null ) {
		if ( $this->elementor_widget_registered ) {
			return;
		}
		if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
			return;
		}
		if ( null === $widgets_manager && class_exists( '\\Elementor\\Plugin' ) ) {
			$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
		}
		if ( ! is_object( $widgets_manager ) ) {
			return;
		}
		require_once SSYS_DIR . 'includes/class-ssys-elementor-widget.php';
		$widget = new SSYS_Elementor_Widget();
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
		} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
		} else {
			return;
		}
		$this->elementor_widget_registered = true;
	}
}

register_activation_hook( __FILE__, array( 'SSYS_Plugin', 'activate' ) );
SSYS_Plugin::instance();

