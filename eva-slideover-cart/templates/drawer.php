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
	aria-label="<?php esc_attr_e( 'Your cart', 'eva-slideover-cart' ); ?>"
>
	<?php do_action( 'eva_sc_before_drawer_header' ); ?>

	<!-- Drawer header -->
	<div class="eva-sc-header">
		<?php
		$header_html = '<h2 class="eva-sc-title">' . esc_html__( 'Cart', 'eva-slideover-cart' ) . '</h2>'
			. '<button class="eva-sc-close" aria-label="' . esc_attr__( 'Close cart', 'eva-slideover-cart' ) . '">'
			. '<span class="eva-sc-control-symbol" aria-hidden="true">×</span>'
			. '</button>';
		$header_kses = array_merge(
			wp_kses_allowed_html( 'post' ),
			[
				'span' => [ 'class' => true, 'aria-hidden' => true ],
			]
		);
		echo wp_kses( apply_filters( 'eva_sc_drawer_header', $header_html ), $header_kses );
		?>
	</div>

	<?php do_action( 'eva_sc_after_drawer_header' ); ?>

	<p class="eva-sc-sr-only eva-sc-status-live" role="status" aria-live="polite" aria-atomic="true"></p>
	<div class="eva-sc-alert" role="alert" hidden>
		<p class="eva-sc-alert-text" dir="auto"></p>
		<button type="button" class="eva-sc-alert-retry eva-sc-btn eva-sc-btn--secondary">
			<?php esc_html_e( 'Retry', 'eva-slideover-cart' ); ?>
		</button>
	</div>

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
	<?php EVA_SC_Render::load_template( 'drawer-footer.php' ); ?>

	<?php do_action( 'eva_sc_after_drawer_footer' ); ?>
</aside>
