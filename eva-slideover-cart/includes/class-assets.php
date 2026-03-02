<?php
/**
 * Asset enqueuing for Eva Slideover Cart.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Assets
 */
class EVA_SC_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue frontend stylesheet and script.
	 */
	public function enqueue(): void {
		if ( ! EVA_SC_Plugin::instance()->is_enabled() ) {
			return;
		}

		// Stylesheet.
		wp_enqueue_style(
			'eva-sc-drawer',
			EVA_SC_URL . 'assets/css/drawer.css',
			[],
			EVA_SC_VERSION
		);

		// Tactic C: inject hiding CSS for theme cart selectors.
		$this->maybe_add_hide_selectors_css();

		// Determine JS dependencies: depend on wc-cart-fragments only if it's registered.
		$js_deps = [ 'jquery' ];
		if ( wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
			$js_deps[] = 'wc-cart-fragments';
		}

		wp_enqueue_script(
			'eva-sc-drawer',
			EVA_SC_URL . 'assets/js/drawer.js',
			$js_deps,
			EVA_SC_VERSION,
			true
		);

		$plugin = EVA_SC_Plugin::instance();

		wp_localize_script(
			'eva-sc-drawer',
			'evaScData',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'eva_sc_nonce' ),
				'openOnAdd'    => $plugin->open_on_add_to_cart(),
				'position'     => $plugin->drawer_position(),
				'wcCartUrl'    => wc_get_cart_url(),
				'wcCheckoutUrl' => wc_get_checkout_url(),
				'i18n'         => [
					'removing'     => __( 'Rimozione...', 'eva-slideover-cart' ),
					'updating'     => __( 'Aggiornamento...', 'eva-slideover-cart' ),
					'errorGeneric' => __( 'Qualcosa e andato storto. Riprova.', 'eva-slideover-cart' ),
					'openCart'     => __( 'Apri carrello', 'eva-slideover-cart' ),
					'closeCart'    => __( 'Chiudi carrello', 'eva-slideover-cart' ),
				],
			]
		);
	}

	/**
	 * Output inline CSS to hide theme mini-cart elements (Tactic C).
	 */
	private function maybe_add_hide_selectors_css(): void {
		$selectors = eva_sc_get_option( 'hide_selectors', [] );
		$selectors = (array) apply_filters( 'eva_sc_disable_theme_cart_hide_selectors', $selectors );
		$selectors = array_filter( array_map( 'trim', $selectors ) );

		if ( empty( $selectors ) ) {
			return;
		}

		$css_selectors = implode( ', ', $selectors );
		$css           = $css_selectors . ' { display: none !important; }';

		wp_add_inline_style( 'eva-sc-drawer', $css );
	}
}
