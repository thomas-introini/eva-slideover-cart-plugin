<?php
/**
 * Admin settings page for Eva Slideover Cart.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVA_SC_Settings
 */
class EVA_SC_Settings {

	/** @var string Option key used in the database. */
	const OPTION_KEY = 'eva_sc_options';

	/** @var string Settings page slug. */
	const PAGE_SLUG = 'eva-slideover-cart';

	/** @var string Settings group name. */
	const SETTINGS_GROUP = 'eva_sc_settings_group';

	/** @var string Settings section ID. */
	const SECTION_GENERAL = 'eva_sc_section_general';

	/** @var string Disable section ID. */
	const SECTION_DISABLE = 'eva_sc_section_disable';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the WooCommerce submenu page.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Slideover Cart', 'eva-slideover-cart' ),
			__( 'Slideover Cart', 'eva-slideover-cart' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register settings, sections and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_KEY,
			[ $this, 'sanitize_options' ]
		);

		// --- General section ---
		add_settings_section(
			self::SECTION_GENERAL,
			__( 'General', 'eva-slideover-cart' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'enabled',
			__( 'Enable Slideover Cart', 'eva-slideover-cart' ),
			[ $this, 'field_checkbox' ],
			self::PAGE_SLUG,
			self::SECTION_GENERAL,
			[
				'key'         => 'enabled',
				'description' => __( 'Activate the drawer cart on the frontend.', 'eva-slideover-cart' ),
			]
		);

		add_settings_field(
			'open_on_add_to_cart',
			__( 'Open on Add to Cart', 'eva-slideover-cart' ),
			[ $this, 'field_checkbox' ],
			self::PAGE_SLUG,
			self::SECTION_GENERAL,
			[
				'key'         => 'open_on_add_to_cart',
				'description' => __( 'Automatically open the cart drawer when a product is added to the cart.', 'eva-slideover-cart' ),
			]
		);

		add_settings_field(
			'free_shipping_threshold',
			__( 'Free Shipping Threshold (€)', 'eva-slideover-cart' ),
			[ $this, 'field_number' ],
			self::PAGE_SLUG,
			self::SECTION_GENERAL,
			[
				'key'         => 'free_shipping_threshold',
				'description' => __( 'Set to 0 to hide the free shipping progress bar.', 'eva-slideover-cart' ),
				'min'         => 0,
				'step'        => 0.01,
			]
		);

		add_settings_field(
			'free_shipping_excluded_classes',
			__( 'Excluded Shipping Classes', 'eva-slideover-cart' ),
			[ $this, 'field_shipping_classes' ],
			self::PAGE_SLUG,
			self::SECTION_GENERAL,
			[
				'key'         => 'free_shipping_excluded_classes',
				'description' => __( 'Products with these shipping classes already include shipping. When every item in the cart belongs to one of these classes, the progress bar will show "free shipping" immediately.', 'eva-slideover-cart' ),
			]
		);

		// --- Disable theme cart section ---
		add_settings_section(
			self::SECTION_DISABLE,
			__( 'Disable Theme Mini-Cart', 'eva-slideover-cart' ),
			[ $this, 'section_disable_description' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'theme_mod_keys',
			__( 'Theme Mod Keys to Disable (Tactic A)', 'eva-slideover-cart' ),
			[ $this, 'field_textarea' ],
			self::PAGE_SLUG,
			self::SECTION_DISABLE,
			[
				'key'         => 'theme_mod_keys',
				'description' => __( 'One theme_mod key per line. The plugin will force these to <code>false</code>. Example: <code>header_cart</code>', 'eva-slideover-cart' ),
				'rows'        => 4,
			]
		);

		add_settings_field(
			'action_callbacks',
			__( 'Action Callbacks to Remove (Tactic B)', 'eva-slideover-cart' ),
			[ $this, 'field_textarea' ],
			self::PAGE_SLUG,
			self::SECTION_DISABLE,
			[
				'key'         => 'action_callbacks',
				'description' => __( 'One entry per line as <code>hook|identifier</code>. Identifier is a function name or <code>ClassName::method</code>. Example: <code>wp_footer|Astra_Cart::render_cart</code>', 'eva-slideover-cart' ),
				'rows'        => 5,
			]
		);

		add_settings_field(
			'hide_selectors',
			__( 'CSS Selectors to Hide (Tactic C)', 'eva-slideover-cart' ),
			[ $this, 'field_textarea' ],
			self::PAGE_SLUG,
			self::SECTION_DISABLE,
			[
				'key'         => 'hide_selectors',
				'description' => __( 'One CSS selector per line. Matching elements will receive <code>display:none !important</code>. Example: <code>.site-header-cart</code>', 'eva-slideover-cart' ),
				'rows'        => 4,
			]
		);
	}

	/**
	 * Description callback for the disable section.
	 */
	public function section_disable_description(): void {
		echo '<p class="description">';
		esc_html_e( 'Use one or more tactics below to suppress the theme\'s own mini-cart without editing theme files.', 'eva-slideover-cart' );
		echo '</p>';
	}

	/**
	 * Sanitize all options on save.
	 *
	 * @param mixed $raw_input Raw POST input.
	 * @return array<string, mixed> Sanitized options.
	 */
	public function sanitize_options( mixed $raw_input ): array {
		$input    = is_array( $raw_input ) ? $raw_input : [];
		$defaults = eva_sc_option_defaults();
		$output   = [];

		$output['enabled']                = ! empty( $input['enabled'] );
		$output['open_on_add_to_cart']    = ! empty( $input['open_on_add_to_cart'] );
		$output['free_shipping_threshold'] = isset( $input['free_shipping_threshold'] )
			? (float) $input['free_shipping_threshold']
			: $defaults['free_shipping_threshold'];

		$raw_classes = isset( $input['free_shipping_excluded_classes'] ) && is_array( $input['free_shipping_excluded_classes'] )
			? $input['free_shipping_excluded_classes']
			: [];
		$output['free_shipping_excluded_classes'] = array_values(
			array_filter( array_map( 'sanitize_key', $raw_classes ) )
		);

		foreach ( [ 'theme_mod_keys', 'action_callbacks', 'hide_selectors' ] as $textarea_key ) {
			$raw_text        = isset( $input[ $textarea_key ] ) ? (string) $input[ $textarea_key ] : '';
			$lines           = explode( "\n", $raw_text );
			$output[ $textarea_key ] = array_values(
				array_filter(
					array_map( 'sanitize_text_field', $lines )
				)
			);
		}

		return $output;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'eva-slideover-cart' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Eva Slideover Cart', 'eva-slideover-cart' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Render a checkbox field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function field_checkbox( array $args ): void {
		$key   = $args['key'];
		$value = eva_sc_get_option( $key, true );
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			checked( $value, true, false ),
			isset( $args['description'] ) ? wp_kses_post( $args['description'] ) : ''
		);
	}

	/**
	 * Render a number input field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function field_number( array $args ): void {
		$key   = $args['key'];
		$value = eva_sc_get_option( $key, 0 );
		$min   = $args['min'] ?? 0;
		$step  = $args['step'] ?? 1;
		printf(
			'<input type="number" name="%1$s[%2$s]" value="%3$s" min="%4$s" step="%5$s" class="small-text">',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( (string) $value ),
			esc_attr( (string) $min ),
			esc_attr( (string) $step )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a list of WooCommerce shipping class checkboxes.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function field_shipping_classes( array $args ): void {
		$key     = $args['key'];
		$saved   = (array) eva_sc_get_option( $key, [] );
		$classes = function_exists( 'WC' ) && WC()->shipping() ? WC()->shipping()->get_shipping_classes() : [];

		if ( empty( $classes ) ) {
			echo '<p class="description">' . esc_html__( 'No shipping classes found. Create them under WooCommerce → Settings → Shipping → Shipping classes.', 'eva-slideover-cart' ) . '</p>';
		} else {
			echo '<fieldset>';
			foreach ( $classes as $class ) {
				$slug    = $class->slug;
				$label   = $class->name;
				$checked = in_array( $slug, $saved, true );
				printf(
					'<label style="display:block;margin-bottom:4px"><input type="checkbox" name="%1$s[%2$s][]" value="%3$s"%4$s> %5$s</label>',
					esc_attr( self::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( $slug ),
					checked( $checked, true, false ),
					esc_html( $label )
				);
			}
			echo '</fieldset>';
		}

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function field_textarea( array $args ): void {
		$key   = $args['key'];
		$rows  = $args['rows'] ?? 4;
		$value = eva_sc_get_option( $key, [] );
		if ( is_array( $value ) ) {
			$value = implode( "\n", $value );
		}
		printf(
			'<textarea name="%1$s[%2$s]" rows="%3$d" class="large-text code">%4$s</textarea>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			(int) $rows,
			esc_textarea( (string) $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>';
		}
	}
}
