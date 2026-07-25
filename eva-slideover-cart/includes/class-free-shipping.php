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
	 * Render the free shipping progress bar from the matching WooCommerce zone.
	 *
	 * @return string HTML output.
	 */
	public static function render(): string {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$rule = self::resolve_rule();
		if ( null === $rule ) {
			return '<div class="eva-sc-free-shipping"></div>';
		}

		$current = (float) apply_filters(
			'eva_sc_free_shipping_current_amount',
			self::get_eligible_subtotal( $rule['ignore_discounts'] )
		);
		$threshold        = (float) apply_filters( 'eva_sc_free_shipping_threshold', $rule['min_amount'] );
		$has_coupon       = self::has_free_shipping_coupon();
		$requires_coupon  = in_array( $rule['requires'], [ 'coupon', 'either', 'both' ], true );
		$requires_minimum = in_array( $rule['requires'], [ 'min_amount', 'either', 'both' ], true );
		$has_minimum      = ! $requires_minimum || $current >= $threshold;
		$eligible         = ( 'either' === $rule['requires'] && ( $has_coupon || $has_minimum ) )
			|| ( 'both' === $rule['requires'] && $has_coupon && $has_minimum )
			|| ( 'coupon' === $rule['requires'] && $has_coupon )
			|| ( 'min_amount' === $rule['requires'] && $has_minimum );
		$remaining        = $requires_minimum ? max( 0.0, $threshold - $current ) : 0.0;
		$percent          = $requires_minimum && $threshold > 0 ? min( 100, (int) round( ( $current / $threshold ) * 100 ) ) : 0;

		if ( $eligible ) {
			$message = esc_html__( 'You qualify for free shipping!', 'eva-slideover-cart' );
			$percent = 100;
		} elseif ( $requires_coupon && ! $has_coupon && ! $requires_minimum ) {
			$message = esc_html__( 'Apply a qualifying coupon for free shipping.', 'eva-slideover-cart' );
		} elseif ( 'both' === $rule['requires'] && ! $has_coupon ) {
			$message = sprintf(
				/* translators: %s: formatted price amount. */
				esc_html__( 'Apply a qualifying coupon and add %s for free shipping.', 'eva-slideover-cart' ),
				wp_kses_post( wc_price( $remaining ) )
			);
		} else {
			$message = sprintf(
				/* translators: %s: formatted price amount. */
				esc_html__( 'Add %s more for free shipping.', 'eva-slideover-cart' ),
				wp_kses_post( wc_price( $remaining ) )
			);
		}

		$description_id = 'eva-sc-free-shipping-message';
		ob_start();
		?>
		<div class="eva-sc-free-shipping" aria-live="polite">
			<div class="eva-sc-progress-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Free shipping progress', 'eva-slideover-cart' ); ?>" aria-describedby="<?php echo esc_attr( $description_id ); ?>" aria-valuenow="<?php echo esc_attr( (string) $percent ); ?>" aria-valuemin="0" aria-valuemax="100">
				<div class="eva-sc-progress-fill" style="width:<?php echo esc_attr( $percent ); ?>%"></div>
			</div>
			<p id="<?php echo esc_attr( $description_id ); ?>" class="eva-sc-progress-msg"><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
		$html = (string) ob_get_clean();

		return (string) apply_filters( 'eva_sc_free_shipping_html', $html, $current, $threshold, $remaining, $percent );
	}

	/**
	 * Resolve the lowest minimum free-shipping method in the current zone.
	 *
	 * @return array{min_amount: float, requires: string, ignore_discounts: bool}|null
	 */
	private static function resolve_rule(): ?array {
		if ( ! class_exists( 'WC_Shipping_Zones' ) || ! WC()->shipping() ) {
			return null;
		}

		$packages = WC()->cart->get_shipping_packages();
		if ( empty( $packages ) ) {
			$packages = WC()->shipping()->get_packages();
		}
		$rules    = [];

		foreach ( $packages as $package ) {
			$zone = WC_Shipping_Zones::get_zone_matching_package( $package );
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( 'free_shipping' !== $method->id || 'yes' !== $method->enabled ) {
					continue;
				}

				$rules[] = [
					'min_amount'       => (float) $method->get_option( 'min_amount', 0 ),
					'requires'         => (string) $method->get_option( 'requires', 'min_amount' ),
					'ignore_discounts' => 'yes' === $method->get_option( 'ignore_discounts', 'no' ),
				];
			}
		}

		if ( empty( $rules ) ) {
			return null;
		}

		usort(
			$rules,
			static function ( array $first, array $second ): int {
				return $first['min_amount'] <=> $second['min_amount'];
			}
		);

		return $rules[0];
	}

	/**
	 * Mirror WooCommerce's free-shipping subtotal calculation.
	 *
	 * @param bool $ignore_discounts Whether the shipping method ignores discounts.
	 * @return float
	 */
	private static function get_eligible_subtotal( bool $ignore_discounts ): float {
		$total = (float) WC()->cart->get_displayed_subtotal();
		if ( ! $ignore_discounts ) {
			$total -= (float) WC()->cart->get_discount_total();
			if ( WC()->cart->display_prices_including_tax() ) {
				$total -= (float) WC()->cart->get_discount_tax();
			}
		}

		return max( 0.0, $total );
	}

	/**
	 * Determine whether the cart has a coupon that grants free shipping.
	 */
	private static function has_free_shipping_coupon(): bool {
		foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );
			if ( $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}
}
