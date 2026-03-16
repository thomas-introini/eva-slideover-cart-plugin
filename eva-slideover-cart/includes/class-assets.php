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
				'requestTimeout' => (int) apply_filters( 'eva_sc_ajax_timeout_ms', 15000 ),
				'openOnAdd'    => $plugin->open_on_add_to_cart(),
				'position'     => $plugin->drawer_position(),
				'wcCartUrl'    => wc_get_cart_url(),
				'wcCheckoutUrl' => wc_get_checkout_url(),
				'i18n'         => [
					'removing'     => __( 'Rimozione...', 'eva-slideover-cart' ),
					'updating'     => __( 'Aggiornamento...', 'eva-slideover-cart' ),
					'errorGeneric' => __( 'Qualcosa e andato storto. Riprova.', 'eva-slideover-cart' ),
					'errorOffline' => __( 'Sei offline. Controlla la connessione e riprova.', 'eva-slideover-cart' ),
					'errorTimeout' => __( 'La richiesta sta impiegando troppo tempo. Riprova.', 'eva-slideover-cart' ),
					'errorValidation' => __( 'Controlla i dati inseriti e riprova.', 'eva-slideover-cart' ),
					'errorPermission' => __( 'La sessione e scaduta o non hai i permessi necessari.', 'eva-slideover-cart' ),
					'errorNotFound' => __( 'Questo elemento non e piu disponibile nel carrello.', 'eva-slideover-cart' ),
					'errorRateLimit' => __( 'Troppe richieste in poco tempo. Attendi e riprova.', 'eva-slideover-cart' ),
					'errorServer' => __( 'Errore temporaneo del server. Riprova tra poco.', 'eva-slideover-cart' ),
					'errorConfig' => __( 'Configurazione incompleta del carrello. Ricarica la pagina.', 'eva-slideover-cart' ),
					'retry'        => __( 'Riprova', 'eva-slideover-cart' ),
					'updatedCart'  => __( 'Carrello aggiornato.', 'eva-slideover-cart' ),
					'updatedQty'   => __( 'Quantita aggiornata.', 'eva-slideover-cart' ),
					'removedItem'  => __( 'Articolo rimosso dal carrello.', 'eva-slideover-cart' ),
					'backOnline'   => __( 'Connessione ripristinata.', 'eva-slideover-cart' ),
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
