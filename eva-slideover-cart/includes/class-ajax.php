<?php
/**
 * AJAX handlers for cart quantity updates and item removal.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Ajax
 */
class EVA_SC_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_eva_sc_update_qty',        [ $this, 'update_qty' ] );
		add_action( 'wp_ajax_nopriv_eva_sc_update_qty', [ $this, 'update_qty' ] );

		add_action( 'wp_ajax_eva_sc_remove_item',        [ $this, 'remove_item' ] );
		add_action( 'wp_ajax_nopriv_eva_sc_remove_item', [ $this, 'remove_item' ] );
	}

	/**
	 * AJAX: update quantity for a cart item.
	 */
	public function update_qty(): void {
		$this->verify_nonce();

		$key = $this->get_cart_item_key();
		$qty = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;

		if ( empty( $key ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid cart item.', 'eva-slideover-cart' ) ], 400 );
		}

		if ( ! array_key_exists( $key, WC()->cart->get_cart() ) ) {
			wp_send_json_error( [ 'message' => __( 'Cart item not found.', 'eva-slideover-cart' ) ], 404 );
		}

		if ( $qty < 1 ) {
			// Treat quantity of 0 as removal.
			WC()->cart->remove_cart_item( $key );
		} else {
			WC()->cart->set_quantity( $key, $qty, true );
		}

		wp_send_json_success( $this->build_fragment_response() );
	}

	/**
	 * AJAX: remove a cart item.
	 */
	public function remove_item(): void {
		$this->verify_nonce();

		$key = $this->get_cart_item_key();

		if ( empty( $key ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid cart item.', 'eva-slideover-cart' ) ], 400 );
		}

		if ( ! array_key_exists( $key, WC()->cart->get_cart() ) ) {
			wp_send_json_error( [ 'message' => __( 'Cart item not found.', 'eva-slideover-cart' ) ], 404 );
		}

		WC()->cart->remove_cart_item( $key );

		wp_send_json_success( $this->build_fragment_response() );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Verify AJAX nonce or send 403.
	 */
	private function verify_nonce(): void {
		if ( ! check_ajax_referer( 'eva_sc_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'eva-slideover-cart' ) ], 403 );
		}
	}

	/**
	 * Retrieve and sanitise the cart item key from the request.
	 *
	 * @return string Sanitized key, or empty string on failure.
	 */
	private function get_cart_item_key(): string {
		if ( empty( $_POST['cart_item_key'] ) ) {
			return '';
		}
		return wc_clean( wp_unslash( (string) $_POST['cart_item_key'] ) );
	}

	/**
	 * Build the JSON payload returned to the browser after a cart mutation.
	 *
	 * Attempts to reuse WC_AJAX::get_refreshed_fragments() if available so the
	 * native wc-cart-fragments.js session hash is updated correctly.  Falls back
	 * to manually applying the woocommerce_add_to_cart_fragments filter.
	 *
	 * @return array<string, mixed>
	 */
	private function build_fragment_response(): array {
		WC()->cart->calculate_totals();

		$fragments  = apply_filters( 'woocommerce_add_to_cart_fragments', [] );
		$cart_hash  = WC()->cart->get_cart_hash();

		return [
			'fragments' => $fragments,
			'cart_hash' => $cart_hash,
		];
	}
}
