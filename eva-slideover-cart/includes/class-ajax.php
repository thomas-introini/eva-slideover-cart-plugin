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
		$this->ensure_cart_available();

		$key = $this->get_cart_item_key();
		$qty = $this->get_quantity();

		if ( empty( $key ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid cart item.', 'eva-slideover-cart' ) ], 400 );
		}

		if ( ! array_key_exists( $key, WC()->cart->get_cart() ) ) {
			wp_send_json_error( [ 'message' => __( 'Cart item not found.', 'eva-slideover-cart' ) ], 404 );
		}

		if ( $qty < 1 ) {
			// Treat quantity of 0 as removal.
			$removed = WC()->cart->remove_cart_item( $key );
			if ( ! $removed ) {
				wp_send_json_error( [ 'message' => __( 'Unable to update this cart item right now.', 'eva-slideover-cart' ) ], 409 );
			}
		} else {
			$updated = WC()->cart->set_quantity( $key, $qty, true );
			if ( false === $updated ) {
				wp_send_json_error( [ 'message' => __( 'Unable to update this cart item right now.', 'eva-slideover-cart' ) ], 409 );
			}
		}

		wp_send_json_success( $this->build_fragment_response() );
	}

	/**
	 * AJAX: remove a cart item.
	 */
	public function remove_item(): void {
		$this->verify_nonce();
		$this->ensure_cart_available();

		$key = $this->get_cart_item_key();

		if ( empty( $key ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid cart item.', 'eva-slideover-cart' ) ], 400 );
		}

		if ( ! array_key_exists( $key, WC()->cart->get_cart() ) ) {
			wp_send_json_error( [ 'message' => __( 'Cart item not found.', 'eva-slideover-cart' ) ], 404 );
		}

		$removed = WC()->cart->remove_cart_item( $key );
		if ( ! $removed ) {
			wp_send_json_error( [ 'message' => __( 'Unable to remove this cart item right now.', 'eva-slideover-cart' ) ], 409 );
		}

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
	 * Ensure WooCommerce cart is available in this request context.
	 */
	private function ensure_cart_available(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'Cart session unavailable. Please reload the page.', 'eva-slideover-cart' ) ], 503 );
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
	 * Retrieve and validate quantity from request.
	 *
	 * @return int
	 */
	private function get_quantity(): int {
		if ( ! isset( $_POST['quantity'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Quantity is required.', 'eva-slideover-cart' ) ], 400 );
		}

		$raw_qty = wc_clean( wp_unslash( (string) $_POST['quantity'] ) );
		if ( '' === $raw_qty || ! is_numeric( $raw_qty ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid quantity.', 'eva-slideover-cart' ) ], 400 );
		}

		return max( 0, (int) wc_stock_amount( $raw_qty ) );
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
