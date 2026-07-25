<?php
/**
 * WooCommerce fragment integration for live cart updates.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Fragments
 */
class EVA_SC_Fragments {

	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_fragments' ], 10 );
	}

	/**
	 * Append plugin-owned fragments to the WooCommerce fragment response.
	 *
	 * Fragment keys must exactly match the outer element selector used in the
	 * drawer markup so WooCommerce's wc-cart-fragments.js can swap them.
	 *
	 * @param array<string, string> $fragments Existing fragments.
	 * @return array<string, string>
	 */
	public function cart_fragments( array $fragments ): array {
		if ( ! EVA_SC_Plugin::instance()->is_enabled() ) {
			return $fragments;
		}

		// Fragment: cart count badge.
		$fragments['span.eva-sc-count'] = $this->render_count();

		// Fragment: items list or empty state.
		$fragments['div.eva-sc-items'] = $this->render_items();

		// Fragment: footer state, subtotal, and cart actions.
		$fragments['div.eva-sc-footer-bar'] = $this->render_footer();

		// Fragment: free shipping progress bar.
		$fragments['div.eva-sc-free-shipping'] = $this->render_free_shipping();

		return $fragments;
	}

	/**
	 * Render the cart count <span>.
	 */
	private function render_count(): string {
		$count = WC()->cart->get_cart_contents_count();
		$count_label = sprintf(
			/* translators: %d: number of items in cart */
			esc_attr( _n( '%d item in cart', '%d items in cart', $count, 'eva-slideover-cart' ) ),
			$count
		);
		ob_start();
		?>
		<span class="eva-sc-count" aria-label="<?php echo esc_attr( $count_label ); ?>"><?php echo esc_html( (string) $count ); ?></span>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the items list wrapper div.
	 */
	private function render_items(): string {
		ob_start();
		$cart_empty = WC()->cart->is_empty();
		?>
		<div class="eva-sc-items">
			<?php
			if ( $cart_empty ) {
				EVA_SC_Render::load_template( 'drawer-empty.php' );
			} else {
				EVA_SC_Render::load_template( 'drawer-items.php' );
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the footer wrapper div.
	 */
	private function render_footer(): string {
		ob_start();
		EVA_SC_Render::load_template( 'drawer-footer.php' );
		return (string) ob_get_clean();
	}

	/**
	 * Render the free shipping progress bar wrapper div.
	 */
	private function render_free_shipping(): string {
		return EVA_SC_Free_Shipping::render();
	}
}
