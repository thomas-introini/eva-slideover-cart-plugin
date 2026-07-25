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

	$product_id  = $product->get_id();
	$qty         = (int) $cart_item['quantity'];
	$product_url = get_permalink( $product->get_id() );
	$thumbnail   = $product->get_image( 'woocommerce_single', [ 'class' => 'eva-sc-item-thumb-img' ] );

	// Quantity limits account for product type, stock rules, and extension filters.
	$min_qty = max( 1, (int) $product->get_min_purchase_quantity() );
	$max_qty = $product->get_max_purchase_quantity();
	$max_qty = is_numeric( $max_qty ) && (int) $max_qty > 0 ? (int) $max_qty : '';

	$is_sold_individually = $product->is_sold_individually();
	$is_in_stock          = $product->is_in_stock();
	$can_change_qty       = ! $is_sold_individually && $is_in_stock;
	$is_on_backorder      = $product->is_on_backorder( $qty );

	$quantity_status = '';
	if ( $is_sold_individually ) {
		$quantity_status = __( 'Limited to one per order.', 'eva-slideover-cart' );
	} elseif ( ! $is_in_stock ) {
		$quantity_status = __( 'This item is currently out of stock.', 'eva-slideover-cart' );
	} elseif ( $is_on_backorder ) {
		$quantity_status = __( 'Available on backorder.', 'eva-slideover-cart' );
	} elseif ( '' !== $max_qty && $qty >= $max_qty ) {
		$quantity_status = __( 'Maximum available quantity reached.', 'eva-slideover-cart' );
	}

	// Includes variations and extra data supplied by extensions, such as add-ons.
	$item_meta_html = wc_get_formatted_cart_item_data( $cart_item );

	// Line price (qty × unit price).
	$line_price = wc_price( (float) $cart_item['line_total'] + (float) $cart_item['line_tax'] );
	?>
	<div class="eva-sc-item" data-key="<?php echo esc_attr( $cart_item_key ); ?>" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>">

		<!-- Thumbnail -->
		<div class="eva-sc-item-thumb">
			<a href="<?php echo esc_url( $product_url ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo wp_kses_post( $thumbnail ); ?>
			</a>
		</div>

		<!-- Details -->
		<div class="eva-sc-item-details">
			<p class="eva-sc-item-name">
				<a href="<?php echo esc_url( $product_url ); ?>" dir="auto"><?php echo esc_html( $product->get_name() ); ?></a>
			</p>

			<?php if ( $item_meta_html ) : ?>
				<div class="eva-sc-item-meta eva-sc-item-variation" dir="auto"><?php echo wp_kses_post( $item_meta_html ); ?></div>
			<?php endif; ?>

			<p class="eva-sc-item-price"><?php echo wp_kses_post( $line_price ); ?></p>

			<?php if ( $can_change_qty ) : ?>
				<!-- Quantity stepper -->
				<div
					class="eva-sc-qty-wrap"
					data-min="<?php echo esc_attr( (string) $min_qty ); ?>"
					<?php if ( '' !== $max_qty ) : ?>
					data-max="<?php echo esc_attr( (string) $max_qty ); ?>"
					<?php endif; ?>
				>
					<button
						class="eva-sc-qty-btn eva-sc-qty-minus"
						data-key="<?php echo esc_attr( $cart_item_key ); ?>"
						aria-label="<?php esc_attr_e( 'Decrease quantity', 'eva-slideover-cart' ); ?>"
						type="button"
					>
						<span class="eva-sc-control-symbol" aria-hidden="true">−</span>
					</button>
					<span
						class="eva-sc-qty-value"
						data-key="<?php echo esc_attr( $cart_item_key ); ?>"
						aria-label="<?php esc_attr_e( 'Quantity', 'eva-slideover-cart' ); ?>"
						aria-live="polite"
					><?php echo esc_html( (string) $qty ); ?></span>
					<button
						class="eva-sc-qty-btn eva-sc-qty-plus"
						data-key="<?php echo esc_attr( $cart_item_key ); ?>"
						<?php if ( '' !== $max_qty && $qty >= $max_qty ) : ?>
						disabled
						<?php endif; ?>
						aria-label="<?php esc_attr_e( 'Increase quantity', 'eva-slideover-cart' ); ?>"
						type="button"
					>
						<span class="eva-sc-control-symbol" aria-hidden="true">+</span>
					</button>
				</div>
			<?php else : ?>
				<span
					class="eva-sc-qty-static"
					aria-label="<?php echo esc_attr( sprintf( __( 'Quantity: %d', 'eva-slideover-cart' ), $qty ) ); ?>"
				>
					<?php echo esc_html( sprintf( __( 'Qty: %d', 'eva-slideover-cart' ), $qty ) ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $quantity_status ) : ?>
				<p class="eva-sc-stock-status"><?php echo esc_html( $quantity_status ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Remove button -->
		<button
			class="eva-sc-remove"
			data-key="<?php echo esc_attr( $cart_item_key ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from cart', 'eva-slideover-cart' ), $product->get_name() ) ); ?>"
			type="button"
		>
			<span class="eva-sc-control-symbol" aria-hidden="true">×</span>
		</button>
	</div>
	<?php
}
