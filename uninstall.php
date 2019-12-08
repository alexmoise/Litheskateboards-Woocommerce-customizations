<?php
/**
 * Uninstall of Litheskateboards Woocommerce customizations plugin.
 * Version: 1.2.1
 * (version above is equal with main plugin file version when this file was updated)
 */

if ( ! defined( 'ABSPATH' ) ) 				{exit(0);}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ))	{die;}

// Deleting fragments function defined again, as it's not available elsewhere at uninstall
function molswc_delete_fragments( $fragment_partial_key ) { global $wpdb; $result = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%{$fragment_partial_key}%'" ); return $result; }

// First check if removal is requested
if ( get_option( 'molswc_delete_options_uninstall' ) ) {
	
	// Then delete cached fragments ..
	$molswc_cache_prefix = 'molswc_cached_fragment';
	molswc_delete_fragments( $molswc_cache_prefix );
	
	// Then define the options (beware there's a copy of this array in lswc-options.php for the moment)
	$molswc_settings_array = array(
		'molswc_estdelivery_instock',
		'molswc_estdelivery_backorder',
		'molswc_estdelivery_preorder',
		'molswc_instock_label',
		'molswc_backorder_label',
		'molswc_preorder_label',
		'molswc_notavailable_label',
		'molswc_pre_order_message',
		'molswc_designated_options',
		'molswc_excluded_categories',
		'molswc_enable_avia_debug',
		'molswc_transient_keys_purging',
		'molswc_instock_label_color',
		'molswc_instock_label_hover_color',
		'molswc_instock_border_color',
		'molswc_instock_border_hover_color',
		'molswc_backorder_label_color',
		'molswc_backorder_label_hover_color',
		'molswc_backorder_border_color',
		'molswc_backorder_border_hover_color',
		'molswc_preorder_label_color',
		'molswc_preorder_label_hover_color',
		'molswc_preorder_border_color',
		'molswc_preorder_border_hover_color',
		'molswc_notavailable_label_color',
		'molswc_notavailable_label_hover_color',
		'molswc_notavailable_border_color',
		'molswc_notavailable_border_hover_color',
		'molswc_true_stock_level_admitted_in_filters',
		'molswc_delete_options_uninstall',
		'molswc_product_background_color',
	);
	// And finally delete each one
	foreach ( $molswc_settings_array as $molswc_setting ) {
		delete_option( $molswc_setting );
	}
}
?>
