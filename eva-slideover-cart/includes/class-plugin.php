<?php
/**
 * Main plugin class — singleton orchestrator.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Plugin
 */
final class EVA_SC_Plugin {

	/** @var EVA_SC_Plugin|null */
	private static ?EVA_SC_Plugin $instance = null;

	/** @var EVA_SC_Assets */
	public EVA_SC_Assets $assets;

	/** @var EVA_SC_Render */
	public EVA_SC_Render $render;

	/** @var EVA_SC_Fragments */
	public EVA_SC_Fragments $fragments;

	/** @var EVA_SC_Ajax */
	public EVA_SC_Ajax $ajax;

	/** @var EVA_SC_Settings */
	public EVA_SC_Settings $settings;

	/** @var EVA_SC_Free_Shipping */
	public EVA_SC_Free_Shipping $free_shipping;

	/**
	 * Get or create singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — hooks everything up.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ], 10 );
	}

	/**
	 * Initialise plugin after all plugins are loaded.
	 */
	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', [ $this, 'notice_woocommerce_missing' ] );
			return;
		}

		load_plugin_textdomain(
			'eva-slideover-cart',
			false,
			dirname( EVA_SC_BASENAME ) . '/languages'
		);

		$this->settings      = new EVA_SC_Settings();
		$this->free_shipping = new EVA_SC_Free_Shipping();
		$this->assets        = new EVA_SC_Assets();
		$this->render        = new EVA_SC_Render();
		$this->fragments     = new EVA_SC_Fragments();
		$this->ajax          = new EVA_SC_Ajax();

		// Disable theme cart output — runs after all plugins and themes have registered hooks.
		add_action( 'wp_loaded', [ $this, 'disable_theme_cart_output' ], 20 );
	}

	/**
	 * Whether the plugin is enabled.
	 */
	public function is_enabled(): bool {
		$enabled = (bool) eva_sc_get_option( 'enabled', true );
		return (bool) apply_filters( 'eva_sc_enabled', $enabled );
	}

	/**
	 * Drawer position (right|left).
	 */
	public function drawer_position(): string {
		return (string) apply_filters( 'eva_sc_drawer_position', 'right' );
	}

	/**
	 * Whether to open the drawer automatically on add-to-cart.
	 */
	public function open_on_add_to_cart(): bool {
		$value = (bool) eva_sc_get_option( 'open_on_add_to_cart', true );
		return (bool) apply_filters( 'eva_sc_open_on_add_to_cart', $value );
	}

	/**
	 * Multi-tactic strategy to disable theme mini-cart output.
	 *
	 * Tactic A: Force theme_mod values to false.
	 * Tactic B: Remove specific action callbacks by hook + identifier.
	 * Tactic C: CSS hiding — handled inside EVA_SC_Assets via inline styles.
	 */
	public function disable_theme_cart_output(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->disable_tactic_a();
		$this->disable_tactic_b();
	}

	/**
	 * Tactic A — override theme_mod values to false so themes that conditionally
	 * render a mini-cart based on a theme_mod setting will suppress their output.
	 */
	private function disable_tactic_a(): void {
		$stored_keys = eva_sc_get_option( 'theme_mod_keys', [] );
		if ( ! is_array( $stored_keys ) ) {
			$stored_keys = [];
		}

		$keys = (array) apply_filters( 'eva_sc_disable_theme_cart_theme_mods', $stored_keys );

		foreach ( $keys as $key ) {
			$key = sanitize_key( $key );
			if ( $key ) {
				add_filter( "theme_mod_{$key}", '__return_false', 99 );
			}
		}
	}

	/**
	 * Tactic B — remove specific registered action callbacks.
	 *
	 * Each entry is a string formatted as "hook|identifier" where identifier
	 * is either a plain function name or "ClassName::method".
	 */
	private function disable_tactic_b(): void {
		global $wp_filter;

		$stored_lines = eva_sc_get_option( 'action_callbacks', [] );
		if ( ! is_array( $stored_lines ) ) {
			$stored_lines = [];
		}

		$entries = (array) apply_filters( 'eva_sc_disable_theme_cart_remove_actions', $stored_lines );

		foreach ( $entries as $line ) {
			$line = trim( (string) $line );
			if ( ! str_contains( $line, '|' ) ) {
				continue;
			}

			[ $hook, $identifier ] = array_map( 'trim', explode( '|', $line, 2 ) );

			if ( empty( $hook ) || empty( $identifier ) || empty( $wp_filter[ $hook ] ) ) {
				continue;
			}

			$this->remove_action_by_identifier( $hook, $identifier );
		}
	}

	/**
	 * Walk $wp_filter for a given hook and remove the callback that matches
	 * the supplied identifier (function name or ClassName::method).
	 *
	 * @param string $hook       Action hook name.
	 * @param string $identifier Callback identifier.
	 */
	private function remove_action_by_identifier( string $hook, string $identifier ): void {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) ) {
			return;
		}

		$is_static = str_contains( $identifier, '::' );
		[ $class_or_fn, $method ] = $is_static
			? array_map( 'trim', explode( '::', $identifier, 2 ) )
			: [ $identifier, '' ];

		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback_id => $callback_data ) {
				$fn      = $callback_data['function'];
				$matched = false;

				if ( is_string( $fn ) && ! $is_static ) {
					$matched = ( $fn === $class_or_fn );
				} elseif ( is_array( $fn ) && $is_static ) {
					$obj_or_class = $fn[0];
					$fn_method    = $fn[1] ?? '';
					if ( is_object( $obj_or_class ) ) {
						$matched = ( get_class( $obj_or_class ) === $class_or_fn && $fn_method === $method );
					} else {
						$matched = ( $obj_or_class === $class_or_fn && $fn_method === $method );
					}
				} elseif ( is_array( $fn ) && ! $is_static ) {
					// Support "ClassName" without method to remove any method.
					$obj_or_class = $fn[0];
					$resolved     = is_object( $obj_or_class ) ? get_class( $obj_or_class ) : $obj_or_class;
					$matched      = ( $resolved === $class_or_fn );
				}

				if ( $matched ) {
					remove_action( $hook, $fn, $priority );
				}
			}
		}
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 */
	public function notice_woocommerce_missing(): void {
		echo '<div class="notice notice-error"><p>';
		echo wp_kses_post(
			sprintf(
				/* translators: %s: WooCommerce plugin link */
				__( '<strong>Eva Slideover Cart</strong> requires %s to be installed and active.', 'eva-slideover-cart' ),
				'<a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>'
			)
		);
		echo '</p></div>';
	}
}
