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

		$has_zero_cost_rate = self::has_zero_cost_shipping_rate();
		$rule               = self::resolve_rule();
		if ( null === $rule && ! $has_zero_cost_rate ) {
			return '<div class="eva-sc-free-shipping"></div>';
		}

		if ( $has_zero_cost_rate ) {
			$current   = 0.0;
			$threshold = 0.0;
			$remaining = 0.0;
			$percent   = 100;
			$message   = esc_html__( 'Free shipping included!', 'eva-slideover-cart' );
		} else {
			$current = (float) apply_filters(
				'eva_sc_free_shipping_current_amount',
				self::get_eligible_subtotal( $rule['ignore_discounts'] )
			);
			$threshold        = (float) apply_filters( 'eva_sc_free_shipping_threshold', $rule['min_amount'] );
			$has_coupon       = self::has_free_shipping_coupon();
			$requires_coupon  = in_array( $rule['requires'], [ 'coupon', 'either', 'both' ], true );
			$requires_minimum = in_array( $rule['requires'], [ 'min_amount', 'either', 'both' ], true );
			$has_minimum      = ! $requires_minimum || $current >= $threshold;
			$eligible         = self::is_eligible( $rule['requires'], $has_coupon, $has_minimum );
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
	 * Determine whether the current cart meets the applicable free-shipping rule.
	 */
	public static function qualifies_for_free_shipping(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$rule = self::resolve_rule();
		if ( null === $rule ) {
			return false;
		}

		$current          = self::get_eligible_subtotal( $rule['ignore_discounts'] );
		$threshold        = (float) $rule['min_amount'];
		$has_coupon       = self::has_free_shipping_coupon();
		$requires_minimum = in_array( $rule['requires'], [ 'min_amount', 'either', 'both' ], true );
		$has_minimum      = ! $requires_minimum || $current >= $threshold;

		return self::is_eligible( $rule['requires'], $has_coupon, $has_minimum );
	}

	/**
	 * Determine whether WooCommerce has a zero-cost shipping rate for the cart.
	 *
	 * Forces shipping calculation when needed so the drawer matches the cart page
	 * for shipping-class and flat-rate free delivery.
	 */
	public static function has_zero_cost_shipping_rate(): bool {
		$packages = self::ensure_shipping_packages();
		if ( empty( $packages ) ) {
			return false;
		}

		$chosen_methods = ( WC()->session ) ? (array) WC()->session->get( 'chosen_shipping_methods', [] ) : [];
		$found_rate     = false;

		foreach ( $packages as $index => $package ) {
			if ( empty( $package['rates'] ) || ! is_array( $package['rates'] ) ) {
				continue;
			}

			$rate = null;
			if ( ! empty( $chosen_methods[ $index ] ) && isset( $package['rates'][ $chosen_methods[ $index ] ] ) ) {
				$rate = $package['rates'][ $chosen_methods[ $index ] ];
			} else {
				foreach ( $package['rates'] as $candidate ) {
					if ( ! is_object( $candidate ) || ! method_exists( $candidate, 'get_cost' ) ) {
						continue;
					}
					if ( null === $rate || (float) $candidate->get_cost() < (float) $rate->get_cost() ) {
						$rate = $candidate;
					}
				}
			}

			if ( ! $rate || ! method_exists( $rate, 'get_cost' ) ) {
				continue;
			}

			$found_rate = true;
			if ( (float) $rate->get_cost() > 0 ) {
				return false;
			}
		}

		return $found_rate;
	}

	/**
	 * Ensure WooCommerce shipping packages include calculated rates for this request.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function ensure_shipping_packages(): array {
		static $packages = null;

		if ( null !== $packages ) {
			return $packages;
		}

		$packages = [];

		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->shipping() || ! WC()->cart->needs_shipping() ) {
			return $packages;
		}

		$existing = WC()->shipping()->get_packages();
		foreach ( $existing as $package ) {
			if ( ! empty( $package['rates'] ) ) {
				$packages = $existing;
				return $packages;
			}
		}

		// Match cart-page behavior when shipping can be shown.
		if ( WC()->cart->show_shipping() ) {
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();
			$packages = WC()->shipping()->get_packages();
			if ( ! empty( $packages ) ) {
				return $packages;
			}
		}

		// Fallback: calculate rates from cart packages even before an address is entered.
		$cart_packages = WC()->cart->get_shipping_packages();
		if ( empty( $cart_packages ) ) {
			return $packages;
		}

		$packages = WC()->shipping()->calculate_shipping( $cart_packages );

		return is_array( $packages ) ? $packages : [];
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

		$packages = self::ensure_shipping_packages();
		if ( empty( $packages ) ) {
			$packages = WC()->cart->get_shipping_packages();
		}
		$rules = [];

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
	 * Evaluate a free-shipping method's configured requirements.
	 *
	 * @param string $requires    Free-shipping requirement mode.
	 * @param bool   $has_coupon  Whether a qualifying coupon is applied.
	 * @param bool   $has_minimum Whether the spend threshold is met.
	 */
	private static function is_eligible( string $requires, bool $has_coupon, bool $has_minimum ): bool {
		return ( 'either' === $requires && ( $has_coupon || $has_minimum ) )
			|| ( 'both' === $requires && $has_coupon && $has_minimum )
			|| ( 'coupon' === $requires && $has_coupon )
			|| ( 'min_amount' === $requires && $has_minimum );
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
