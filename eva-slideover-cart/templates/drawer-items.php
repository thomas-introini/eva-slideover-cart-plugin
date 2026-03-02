<?php
/**
 * Cart items list template.
 *
 * Rendered inside .eva-sc-items when the cart is not empty.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
	/** @var WC_Product $product */
	$product = $cart_item['data'];

	if ( ! $product || ! $product->exists() ) {
		continue;
	}

	$product_id   = $product->get_id();
	$qty          = (int) $cart_item['quantity'];
	$product_url  = get_permalink( $product->get_id() );
	$thumbnail    = $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'eva-sc-item-thumb-img' ] );

	// Stock-aware max quantity.
	$max_qty = '';
	if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
		$max_qty = $product->get_stock_quantity() ?? '';
	}

	// Variation data.
	$variation_html = '';
	if ( ! empty( $cart_item['variation'] ) ) {
		$variation_html = wc_get_formatted_cart_item_data( $cart_item );
	}

	// Line price (qty × unit price).
	$line_price = wc_price( (float) $cart_item['line_total'] + (float) $cart_item['line_tax'] );
	?>
	<div class="eva-sc-item" data-key="<?php echo esc_attr( $cart_item_key ); ?>">

		<!-- Thumbnail -->
		<div class="eva-sc-item-thumb">
			<a href="<?php echo esc_url( $product_url ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo wp_kses_post( $thumbnail ); ?>
			</a>
		</div>

		<!-- Details -->
		<div class="eva-sc-item-details">
			<p class="eva-sc-item-name">
				<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
			</p>

			<?php if ( $variation_html ) : ?>
				<div class="eva-sc-item-variation"><?php echo wp_kses_post( $variation_html ); ?></div>
			<?php endif; ?>

			<p class="eva-sc-item-price"><?php echo wp_kses_post( $line_price ); ?></p>

			<!-- Quantity stepper -->
			<div class="eva-sc-qty-wrap">
				<button
					class="eva-sc-qty-btn eva-sc-qty-minus"
					data-key="<?php echo esc_attr( $cart_item_key ); ?>"
					aria-label="<?php esc_attr_e( 'Diminuisci quantita', 'eva-slideover-cart' ); ?>"
					type="button"
				>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false"><line x1="2" y1="8" x2="14" y2="8"/></svg>
				</button>
				<input
					class="eva-sc-qty-input"
					type="number"
					value="<?php echo esc_attr( (string) $qty ); ?>"
					min="1"
					<?php if ( '' !== $max_qty ) : ?>
					max="<?php echo esc_attr( (string) $max_qty ); ?>"
					<?php endif; ?>
					step="1"
					data-key="<?php echo esc_attr( $cart_item_key ); ?>"
					aria-label="<?php esc_attr_e( 'Quantita', 'eva-slideover-cart' ); ?>"
				>
				<button
					class="eva-sc-qty-btn eva-sc-qty-plus"
					data-key="<?php echo esc_attr( $cart_item_key ); ?>"
					<?php if ( '' !== $max_qty ) : ?>
					data-max="<?php echo esc_attr( (string) $max_qty ); ?>"
					<?php endif; ?>
					aria-label="<?php esc_attr_e( 'Aumenta quantita', 'eva-slideover-cart' ); ?>"
					type="button"
				>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
				</button>
			</div>
		</div>

		<!-- Remove button -->
		<button
			class="eva-sc-remove"
			data-key="<?php echo esc_attr( $cart_item_key ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Rimuovi %s dal carrello', 'eva-slideover-cart' ), $product->get_name() ) ); ?>"
			type="button"
		>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>
	<?php
}
