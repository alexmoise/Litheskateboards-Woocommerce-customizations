<?php
/**
 * Variable product add to cart 
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.4.0
 *
 * Modified to use radio buttons instead of dropdowns
 * @author 8manos
 * 
 * Lithe version: 1.5.12
 * (version above is equal with main plugin file version when this file was updated)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $product;
global $woocommerce;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); 

// Get the Add to Cart display type option:
$molswc_choose_single_product_display_type = get_post_meta ($product->get_id(), 'molswc_choose_single_product_display_type')[0];

// Get the separate forms locations, for Board type products and for Soft goods type products
$forms_directory = untrailingslashit( plugin_dir_path( __FILE__ ) );

// Set the type of form to be displayed
if ( $molswc_choose_single_product_display_type == 'soft' )  { $included_form = $forms_directory.'/form-soft.php'; }
if ( $molswc_choose_single_product_display_type == 'board' ) { $included_form = $forms_directory.'/form-board.php'; }
// If something gone south then default to board type
if ( !$included_form ) { $included_form = $forms_directory.'/form-board.php'; }

// === Now start setting up the fragment cache
// building the cache args array
$fragm_cache_args = array();
// $fragm_cache_args['disable'] = 'true'; // could disable cache completely, for debug purposes

// building a unique cache key here: add conditions below so the key changes when the form content should change; the order of elements is important too
$fragm_cache_key_build['identifier'] = 'prod_form'; // set a unique name at the beginning of the transient, we'll use this later do delete these transients
$fragm_cache_key_build['productid'] = $product->get_id(); // add product ID in the mix, so the product forms does not mix :-)
$fragm_cache_key_build['isproduct'] = is_product() ?: 0; // check if is a product page, because the popup access creates the form without the "wm_pvar" data
$fragm_cache_key_build['usersubscript'] = molswc_check_user_subscription_able(); // add user subscription-able condition to the key, because these users have different prices
// $fragm_cache_key_build['userlogged'] = is_user_logged_in() ?: 0; // check if there's a user authenticated, otherwise return 0

$fragm_cache_full_product_key = $fragm_cache_key_build['identifier'].'_'.$fragm_cache_key_build['productid'].'_1_'.$fragm_cache_key_build['usersubscript']; // Simulate key name for IS_PRODUCT situation
$fragm_cache_full_product_check = molswc_check_fragments($fragm_cache_full_product_key); // Then check if the IS_PRODUCT situation is already having a fragment
if($fragm_cache_key_build['isproduct'] == 0 && $fragm_cache_full_product_check == 1) { $fragm_cache_key_build['isproduct'] = 1; } // And use the IS_PRODUCT fragment if available, instead of generating a new one

$fragm_cache_key = implode('_', $fragm_cache_key_build); // defining a unique key for caching the forms uniquely for each board, user status, etc.
if ( !Pj_Fragment_Cache::output( $fragm_cache_key, $fragm_cache_args ) ) { // conditionally call the cache output right here before building the whole <form>:

include($included_form); // Now include the form as defined above

Pj_Fragment_Cache::store();
} // Since the <form> is built at this moment let's close fragment cache call here.

do_action( 'woocommerce_after_add_to_cart_form' ); 

?>
