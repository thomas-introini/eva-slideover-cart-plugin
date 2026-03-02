<?php
/**
 * Empty cart state template.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="eva-sc-empty">
	<svg class="eva-sc-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true" focusable="false">
		<path d="M8 8h8l5 30h26l5-20H18" stroke-width="2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
		<circle cx="27" cy="52" r="3" fill="currentColor"/>
		<circle cx="43" cy="52" r="3" fill="currentColor"/>
	</svg>
	<p class="eva-sc-empty-msg"><?php esc_html_e( 'Il tuo carrello e vuoto.', 'eva-slideover-cart' ); ?></p>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="eva-sc-btn eva-sc-btn--primary">
		<?php esc_html_e( 'Continua gli acquisti', 'eva-slideover-cart' ); ?>
	</a>
</div>
