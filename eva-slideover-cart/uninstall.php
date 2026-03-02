<?php
/**
 * Plugin uninstall routine.
 *
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes all plugin-owned database options.
 *
 * @package Eva_Slideover_Cart
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'eva_sc_options' );
