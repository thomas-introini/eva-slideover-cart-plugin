<?php
/**
 * Helper functions for Eva Slideover Cart.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default option values. Merged with stored values on retrieval.
 *
 * @return array<string, mixed>
 */
function eva_sc_option_defaults(): array {
	return [
		'enabled'                => true,
		'free_shipping_threshold' => 0.0,
		'open_on_add_to_cart'    => true,
		'theme_mod_keys'         => [],
		'action_callbacks'       => [],
		'hide_selectors'         => [],
	];
}

/**
 * Get a single plugin option with fallback to defaults.
 *
 * @param string $key     Option key.
 * @param mixed  $default Override default (null = use built-in default).
 * @return mixed
 */
function eva_sc_get_option( string $key, mixed $default = null ): mixed {
	$stored   = get_option( 'eva_sc_options', [] );
	$defaults = eva_sc_option_defaults();

	$value = array_key_exists( $key, $stored ) ? $stored[ $key ] : ( $defaults[ $key ] ?? null );

	if ( null === $value && null !== $default ) {
		$value = $default;
	}

	return $value;
}

/**
 * Locate a template file, allowing theme overrides.
 *
 * Theme authors can place overrides in:
 *   wp-content/themes/<theme>/eva-slideover-cart/<template>
 *
 * @param string $template_name Template filename (e.g. 'drawer.php').
 * @return string Absolute path to the template file.
 */
function eva_sc_locate_template( string $template_name ): string {
	$theme_file  = trailingslashit( get_stylesheet_directory() ) . 'eva-slideover-cart/' . $template_name;
	$parent_file = trailingslashit( get_template_directory() ) . 'eva-slideover-cart/' . $template_name;
	$plugin_file = EVA_SC_PATH . 'templates/' . $template_name;

	if ( file_exists( $theme_file ) ) {
		return $theme_file;
	}

	if ( file_exists( $parent_file ) ) {
		return $parent_file;
	}

	return $plugin_file;
}

/**
 * Format a monetary amount using WooCommerce price formatting.
 *
 * @param float $amount Raw amount.
 * @return string HTML price string.
 */
function eva_sc_format_price( float $amount ): string {
	return wc_price( $amount );
}

/**
 * Allowed HTML for the trigger icon (used with wp_kses).
 * Supports default SVG and Font Awesome / Line Awesome span or i markup.
 *
 * @return array<string, array<string, bool>>
 */
function eva_sc_trigger_icon_allowed_html(): array {
	return [
		'svg'  => [
			'class'       => true,
			'xmlns'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		],
		'path' => [ 'd' => true ],
		'line' => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ],
		'span' => [ 'class' => true, 'aria-hidden' => true ],
		'i'    => [ 'class' => true, 'aria-hidden' => true ],
	];
}
