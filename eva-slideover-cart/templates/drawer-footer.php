<?php
/**
 * Drawer footer template.
 *
 * Rendered as a WooCommerce fragment to keep calls to action aligned with
 * the current cart state.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

$footer_html = '';
if ( ! WC()->cart->is_empty() ) {
	$needs_shipping = WC()->cart->needs_shipping();
	if ( $needs_shipping ) {
		EVA_SC_Free_Shipping::ensure_shipping_packages();
	}

	$shipping_amount         = WC()->cart->get_cart_shipping_total();
	$has_shipping_cost       = (float) WC()->cart->get_shipping_total() > 0 || (float) WC()->cart->get_shipping_tax() > 0;
	$qualifies_free_shipping = $needs_shipping && EVA_SC_Free_Shipping::qualifies_for_free_shipping();
	$has_free_shipping_rate  = EVA_SC_Free_Shipping::has_zero_cost_shipping_rate();
	$has_calculated_shipping = ! $needs_shipping || $qualifies_free_shipping || $has_free_shipping_rate || $has_shipping_cost || ( WC()->customer && WC()->customer->has_calculated_shipping() );
	$shipping_label          = esc_html__( 'Shipping', 'eva-slideover-cart' );
	$total_label             = $needs_shipping && ! $has_calculated_shipping
		? esc_html__( 'Total so far', 'eva-slideover-cart' )
		: esc_html__( 'Total', 'eva-slideover-cart' );
	$shipping_value          = $qualifies_free_shipping || $has_free_shipping_rate
		? esc_html__( 'Free', 'eva-slideover-cart' )
		: ( $needs_shipping && ! $has_calculated_shipping
		? esc_html__( 'Calculated at checkout', 'eva-slideover-cart' )
		: $shipping_amount );
	$coupon_rows             = '';

	foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
		$coupon          = new WC_Coupon( $coupon_code );
		$discount_amount = (float) WC()->cart->get_coupon_discount_amount( $coupon_code, WC()->cart->display_cart_ex_tax );
		$effects         = [];

		if ( $discount_amount > 0 ) {
			$effects[] = '-' . wc_price( $discount_amount );
		}

		if ( $coupon->get_free_shipping() ) {
			$effects[] = esc_html__( 'Free shipping', 'eva-slideover-cart' );
		}

		if ( empty( $effects ) ) {
			continue;
		}

		$coupon_rows .= '<div class="eva-sc-total-row eva-sc-coupon">'
			. '<span class="eva-sc-total-label">'
			. sprintf(
				/* translators: %s: coupon code */
				esc_html__( 'Coupon: %s', 'eva-slideover-cart' ),
				esc_html( $coupon_code )
			)
			. '</span>'
			. '<span class="eva-sc-total-amount">' . wp_kses_post( implode( ' <span aria-hidden="true">·</span> ', $effects ) ) . '</span>'
			. '</div>';
	}

	$footer_html = '<div class="eva-sc-totals">'
		. '<div class="eva-sc-total-row eva-sc-subtotal">'
		. '<span class="eva-sc-total-label">' . esc_html__( 'Subtotal', 'eva-slideover-cart' ) . '</span>'
		. '<span class="eva-sc-total-amount">' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</span>'
		. '</div>'
		. $coupon_rows
		. '<div class="eva-sc-total-row eva-sc-shipping">'
		. '<span class="eva-sc-total-label">' . $shipping_label . '</span>'
		. '<span class="eva-sc-total-amount">' . wp_kses_post( $shipping_value ) . '</span>'
		. '</div>'
		. '<div class="eva-sc-total-row eva-sc-total">'
		. '<span class="eva-sc-total-label">' . $total_label . '</span>'
		. '<span class="eva-sc-total-amount">' . wp_kses_post( WC()->cart->get_total() ) . '</span>'
		. '</div>'
		. '<div class="eva-sc-actions">'
		. '<a href="' . esc_url( wc_get_cart_url() ) . '" class="eva-sc-btn eva-sc-btn--secondary">' . esc_html__( 'View cart', 'eva-slideover-cart' ) . '</a>'
		. '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="eva-sc-btn eva-sc-btn--primary">' . esc_html__( 'Checkout', 'eva-slideover-cart' ) . '</a>'
		. '</div>';
}
?>
<div class="eva-sc-footer-bar">
	<?php echo wp_kses_post( apply_filters( 'eva_sc_drawer_footer', $footer_html ) ); ?>
</div>
