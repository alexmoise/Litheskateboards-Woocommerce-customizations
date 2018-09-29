<?php
/**
 * Uninstall of Litheskateboards Woocommerce customizations plugin.
 * Version: 1.0.27
 * (version above is equal with main plugin file version when this file was updated)
 */

if ( ! defined( 'ABSPATH' ) ) 				{exit(0);}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ))	{die;}

// First check if removal is requested
if ( get_option( 'molswc_delete_options_uninstall' ) ) {
	
	// Then delete cached fragments ..
	molswc_delete_fragments( 'molswc_cached_fragment' );
	
	// Next define options to remove as variables ..
	$molswc_option_remove_estdelivery_instock			= 'molswc_estdelivery_instock';
	$molswc_option_remove_estdelivery_backorder			= 'molswc_estdelivery_backorder';
	$molswc_option_remove_pre_order_message				= 'molswc_pre_order_message';
	$molswc_option_remove_designated_options			= 'molswc_designated_options';
	$molswc_option_remove_excluded_categories			= 'molswc_excluded_categories';
	$molswc_option_remove_enable_avia_debug				= 'molswc_enable_avia_debug';
	$molswc_option_remove_transient_keys_purging		= 'molswc_transient_keys_purging';
	$molswc_option_remove_delete_options_uninstall		= 'molswc_delete_options_uninstall';

	// And finally remove the options!
	delete_option( $molswc_option_remove_estdelivery_instock );
	delete_option( $molswc_option_remove_estdelivery_backorder );
	delete_option( $molswc_option_remove_pre_order_message );
	delete_option( $molswc_option_remove_designated_options );
	delete_option( $molswc_option_remove_excluded_categories );
	delete_option( $molswc_option_remove_enable_avia_debug );
	delete_option( $molswc_option_remove_transient_keys_purging );
	delete_option( $molswc_option_remove_delete_options_uninstall );
}
?>
