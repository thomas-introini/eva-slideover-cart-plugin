<?php
/**
 * Frontend rendering: trigger button shortcode and drawer shell.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Render
 */
class EVA_SC_Render {

	public function __construct() {
		add_shortcode( 'eva_slideover_cart_trigger', [ $this, 'shortcode_trigger' ] );

		// Print the drawer shell once in the page body.
		if ( function_exists( 'wp_body_open' ) ) {
			add_action( 'wp_body_open', [ $this, 'print_drawer' ], 50 );
		}
		// Fallback for themes that do not call wp_body_open().
		add_action( 'wp_footer', [ $this, 'print_drawer' ], 5 );
	}

	/**
	 * Shortcode: [eva_slideover_cart_trigger]
	 *
	 * @return string Trigger button HTML.
	 */
	public function shortcode_trigger(): string {
		if ( ! EVA_SC_Plugin::instance()->is_enabled() ) {
			return '';
		}
		return $this->get_trigger_html();
	}

	/**
	 * Build the trigger button HTML.
	 *
	 * @return string
	 */
	public function get_trigger_html(): string {
		$count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$count_label = sprintf(
			/* translators: %d: number of items in cart */
			esc_attr( _n( '%d articolo nel carrello', '%d articoli nel carrello', $count, 'eva-slideover-cart' ) ),
			$count
		);

		$default_icon = '<svg class="eva-sc-trigger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">'
			. '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>'
			. '<line x1="3" y1="6" x2="21" y2="6"/>'
			. '<path d="M16 10a4 4 0 0 1-8 0"/>'
			. '</svg>';
		$icon_html = (string) apply_filters( 'eva_sc_trigger_icon_html', $default_icon );

		ob_start();
		?>
		<button class="eva-sc-trigger" aria-label="<?php esc_attr_e( 'Apri carrello', 'eva-slideover-cart' ); ?>" aria-expanded="false" aria-controls="eva-sc-drawer">
			<?php echo wp_kses( $icon_html, eva_sc_trigger_icon_allowed_html() ); ?>
			<span class="eva-sc-count" aria-label="<?php echo esc_attr( $count_label ); ?>"><?php echo esc_html( (string) $count ); ?></span>
		</button>
		<?php
		$html = ob_get_clean();

		return (string) apply_filters( 'eva_sc_trigger_html', $html, $count );
	}

	/**
	 * Output the full drawer shell into the page.
	 * Uses a flag to ensure it is printed only once even if both hooks fire.
	 */
	public function print_drawer(): void {
		static $printed = false;

		if ( $printed || ! EVA_SC_Plugin::instance()->is_enabled() ) {
			return;
		}

		$printed = true;

		$template = eva_sc_locate_template( 'drawer.php' );
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Load a sub-template inside the drawer context.
	 *
	 * @param string $template_name Template filename.
	 */
	public static function load_template( string $template_name ): void {
		$path = eva_sc_locate_template( $template_name );
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
