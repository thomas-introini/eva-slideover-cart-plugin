<?php
/**
 * Drawer shell template.
 *
 * Printed once per page load inside <body> via wp_body_open / wp_footer.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

$position = EVA_SC_Plugin::instance()->drawer_position();
$classes  = apply_filters(
	'eva_sc_drawer_classes',
	[ 'eva-sc-drawer', 'eva-sc-drawer--' . esc_attr( $position ) ]
);
$classes_str = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
?>
<!-- Eva Slideover Cart -->
<div class="eva-sc-overlay" aria-hidden="true"></div>

<aside
	id="eva-sc-drawer"
	class="<?php echo esc_attr( $classes_str ); ?>"
	role="dialog"
	aria-modal="true"
	aria-hidden="true"
	aria-label="<?php esc_attr_e( 'Il tuo carrello', 'eva-slideover-cart' ); ?>"
>
	<?php do_action( 'eva_sc_before_drawer_header' ); ?>

	<!-- Drawer header -->
	<div class="eva-sc-header">
		<?php
		$header_html = '<h2 class="eva-sc-title">' . esc_html__( 'Carrello', 'eva-slideover-cart' ) . '</h2>'
			. '<button class="eva-sc-close" aria-label="' . esc_attr__( 'Chiudi carrello', 'eva-slideover-cart' ) . '">'
			. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">'
			. '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'
			. '</svg>'
			. '</button>';
		$header_kses = array_merge(
			wp_kses_allowed_html( 'post' ),
			[
				'svg'  => [ 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true ],
				'line' => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ],
			]
		);
		echo wp_kses( apply_filters( 'eva_sc_drawer_header', $header_html ), $header_kses );
		?>
	</div>

	<?php do_action( 'eva_sc_after_drawer_header' ); ?>

	<!-- Free shipping progress bar -->
	<?php echo EVA_SC_Free_Shipping::render(); // phpcs:ignore WordPress.Security.EscapeOutput -- render() returns escaped HTML. ?>

	<!-- Cart body: items list -->
	<div class="eva-sc-body">
		<div class="eva-sc-items">
			<?php
			if ( WC()->cart->is_empty() ) {
				EVA_SC_Render::load_template( 'drawer-empty.php' );
			} else {
				EVA_SC_Render::load_template( 'drawer-items.php' );
			}
			?>
		</div>
	</div>

	<?php do_action( 'eva_sc_after_items' ); ?>

	<!-- Sticky footer bar -->
	<div class="eva-sc-footer-bar">
		<?php
		$footer_html = '<div class="eva-sc-subtotal">'
			. '<span class="eva-sc-subtotal-label">' . esc_html__( 'Subtotale', 'eva-slideover-cart' ) . '</span>'
			. '<span class="eva-sc-subtotal-amount">' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</span>'
			. '</div>'
			. '<div class="eva-sc-actions">'
			. '<a href="' . esc_url( wc_get_cart_url() ) . '" class="eva-sc-btn eva-sc-btn--secondary">' . esc_html__( 'Vai al carrello', 'eva-slideover-cart' ) . '</a>'
			. '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="eva-sc-btn eva-sc-btn--primary">' . esc_html__( 'Vai alla cassa', 'eva-slideover-cart' ) . '</a>'
			. '</div>';
		echo wp_kses_post( apply_filters( 'eva_sc_drawer_footer', $footer_html ) );
		?>
	</div>

	<?php do_action( 'eva_sc_after_drawer_footer' ); ?>
</aside>
