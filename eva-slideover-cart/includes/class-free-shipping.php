<?php
/**
 * Free shipping progress bar logic.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Free_Shipping
 */
class EVA_SC_Free_Shipping {

	public function __construct() {
		// No hooks needed — render() is called directly by fragments and templates.
	}

	/**
	 * Render the free shipping progress bar HTML.
	 *
	 * Returns an empty string when:
	 *  - WooCommerce cart is not available.
	 *  - Threshold is 0 (disabled).
	 *
	 * @return string HTML output.
	 */
	public static function render(): string {
		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return '';
		}

		$threshold = (float) apply_filters(
			'eva_sc_free_shipping_threshold',
			eva_sc_get_option( 'free_shipping_threshold', 0.0 )
		);

		if ( $threshold <= 0 ) {
			return '';
		}

		$current = (float) apply_filters(
			'eva_sc_free_shipping_current_amount',
			WC()->cart->get_displayed_subtotal()
		);

		$remaining = max( 0.0, $threshold - $current );
		$percent   = min( 100, (int) round( ( $current / $threshold ) * 100 ) );

		if ( $remaining <= 0 ) {
			$message = esc_html__( 'You have free shipping!', 'eva-slideover-cart' );
			$percent = 100;
		} else {
			$message = sprintf(
				/* translators: %s: formatted price amount */
				esc_html__( 'Add %s more for free shipping', 'eva-slideover-cart' ),
				wp_kses_post( wc_price( $remaining ) )
			);
		}

		ob_start();
		?>
		<div class="eva-sc-free-shipping" aria-live="polite">
			<div class="eva-sc-progress-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $percent ); ?>" aria-valuemin="0" aria-valuemax="100">
				<div class="eva-sc-progress-fill" style="width:<?php echo esc_attr( $percent ); ?>%"></div>
			</div>
			<p class="eva-sc-progress-msg"><?php echo $message; // Already escaped above. ?></p>
		</div>
		<?php
		$html = ob_get_clean();

		return (string) apply_filters( 'eva_sc_free_shipping_html', $html, $current, $threshold, $remaining, $percent );
	}
}
