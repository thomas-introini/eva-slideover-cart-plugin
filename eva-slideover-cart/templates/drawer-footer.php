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
	$footer_html = '<div class="eva-sc-subtotal">'
		. '<span class="eva-sc-subtotal-label">' . esc_html__( 'Subtotal', 'eva-slideover-cart' ) . '</span>'
		. '<span class="eva-sc-subtotal-amount">' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</span>'
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
