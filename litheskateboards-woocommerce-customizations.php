<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in product popup and in single product page, 2. have the last option (Payment Plan) show up only after selecting a Width corresponding to a Model, 3. jump directly to checkout after selecting the last option (Payment Plan). Works based on "Quick View WooCommerce" by XootiX for popup, on "WooCommerce Variation Price Hints" by Wisslogic for price calculations and also on "WC Variations Radio Buttons" for transforming selects into buttons. Also uses the "YITH Pre-Order for WooCommerce" plugin as a base plugin for handling the Pre Order functions. For details/troubleshooting please contact me at <a href="https://moise.pro/contact/">https://moise.pro/contact/</a>
 * Version: 1.8.2
 * Author: Alex Moise
 * Author URI: https://moise.pro
 * WC requires at least: 4.9.0
 * WC tested up to: 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// === Some general preparations and customization first:
// Adding admin options
include( plugin_dir_path( __FILE__ ) . 'lswc-options.php' );
// Adding own CSS
add_action( 'wp_enqueue_scripts', 'molswc_adding_styles', 999999999 ); // yeah, "avia-merged-styles" has 999999 :-P
function molswc_adding_styles() {
	wp_register_style('lswc-styles', plugins_url('lswc.css', __FILE__));
	wp_enqueue_style('lswc-styles');
}
// Adding own JS
add_action( 'wp_enqueue_scripts', 'molswc_adding_scripts', 9999999 );
function molswc_adding_scripts() {
	wp_register_script('lswc-script', plugins_url('lswc.js', __FILE__), array('jquery'), '', true);
	wp_enqueue_script('lswc-script');
}
// Adding the Settings link in Plugins Page, next to Deactivate link
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'molswc_plugin_action_links' );
function molswc_plugin_action_links( $molswclinks ) {
	$molswclinks = array_merge( array(
		'<a target="_blank" href="' . esc_url( admin_url( '/admin.php?page=lithe-options' ) ) . '">' . __( 'Settings' ) . '</a>'
	), $molswclinks );
	return $molswclinks;
}
// Adding mobile app capability and theme color metas
add_action( 'wp_head', 'molswc_webapp_meta' ); 
function molswc_webapp_meta() {
    echo '<meta name="mobile-web-app-capable" content="yes">';
	echo '<meta name="theme-color" content="#000000" />';
}
// Debug variable to file; call it wherever it's needed along with the variable to be outputted and with a label for better understanding
function molswc_debug_to_file($output_label, $something_to_output) {
	$timestamp = date("Y-m-d H:i:s");
	if ( defined('ABSPATH') ) { $path_to_wp_content = ABSPATH.'wp-content/'; $molswc_file = $path_to_wp_content.'testdata.txt';} else { return; }
	file_put_contents($molswc_file, "\n".$timestamp.": ### Debug_to_file triggered ### ", FILE_APPEND | LOCK_EX);
	$readable_thing = print_r($something_to_output, true); 
	file_put_contents($molswc_file, "\n".$timestamp.": ".$output_label." = ".$readable_thing."  ", FILE_APPEND | LOCK_EX);	
}
// Get rid of original JS from WC Variations Price Hints (all its functions are changed and present now in lswc.js)
add_action('wp_print_scripts','molswc_remove_wcvarhints_js');
function molswc_remove_wcvarhints_js() {
	if ( is_product() ) { // we need it in shop and categories because there's no "current" product in these pages, unless popup pops up with one
		wp_dequeue_script('wm_variation_price_hints_script');
		wp_deregister_script('wm_variation_price_hints_script');
	}
}
// Get rid of the original spinner function of the Smart Product Viewer plugin, and enqueue the one with the customized JS code
add_action('wp_print_scripts','molswc_replace_spinner_js');
function molswc_replace_spinner_js() {
    wp_dequeue_script('smart-product');
    wp_deregister_script('smart-product');
	wp_register_script('smart-product-custom', plugins_url('smart.product.min.js', __FILE__));
	wp_enqueue_script('smart-product-custom');
}
// Enable Avia Builder Debug, for easily copy/paste page contents (an Enfold Theme thing)
add_action('avia_builder_mode', "molswc_builder_set_debug");
if ( ! function_exists( 'molswc_builder_set_debug' ) ) {
	function molswc_builder_set_debug() {
		if ( get_option( 'molswc_enable_avia_debug' ) ) { return "debug"; }
	}
}
// Disable update notifications for WooCommerce Variation Price Hints plugin
add_filter( 'site_transient_update_plugins', 'molswc_remove_update_notifications' );
function molswc_remove_update_notifications( $value ) {
    if ( isset( $value ) && is_object( $value ) ) {
        unset( $value->response[ 'variation-price-hints/WM_Variation_Price_Hints.php' ] );
    }
    return $value;
}

// === Various Woocommerce Shop and Product customizations below:
// Shop page title changed to "Shop - Lithe Skateboards"
add_filter('pre_get_document_title', 'molswc_shop_title_tag');
function molswc_shop_title_tag(){
	if ( is_shop() ) {
		return 'Shop - Lithe Skateboards';
	}
}
// Add the Woocommerce notification right after opening div#main container, so the notices get displayed in any page
add_action( 'ava_after_main_container', 'molswc_custom_notices' );
function molswc_custom_notices() { 
	echo '<div class="molswc_custom_notices_wrapper">';
	wc_print_notices(); 
	echo '</div>';
}

// Go straight to Checkout when a Payment Method button has been pressed
// add_filter('woocommerce_add_to_cart_redirect', 'molswc_go_to_checkout');
function molswc_go_to_checkout() {
	global $woocommerce;
	$checkout_url = wc_get_checkout_url();
 /* // Trying to conditionally redirect to cart
	// Best thing so far is to redirect if "pay_in_full = TRUE" but that will also redirect when clicking Buy Now, beside the Add to Cart button ...
	$cart = WC()->cart->get_cart();
	$variation = end($cart)['variation'];
	$output_label = 'Try on 3 - ';
	$something_to_output = $cart;
	molswc_debug_to_file($output_label, $something_to_output);
 */
	return $checkout_url;
}
// Extend conditional variations limit
add_filter( 'woocommerce_ajax_variation_threshold', 'molswc_wc_ajax_variation_threshold', 10, 2 );
function molswc_wc_ajax_variation_threshold( $qty, $product ) {
    return 50;
}
// Get the product options we'll use to separate buttons lists
function molswc_trim_value(&$value) { $value = trim($value); } // Just another simple trim function, used below and maybe somewhere else :-)
function molswc_designated_options() {
	$raw_designated_options = strip_tags(get_option( 'molswc_designated_options' )); // get raw options as defined in options DB table
	$designated_options = explode(',', $raw_designated_options); // create an array with options
	array_walk($designated_options, 'molswc_trim_value'); // remove possible white space at the beginning or the end of each array element (using previously defined trim function)
	return $designated_options; // finally return the array to wherever is needed
}
// Get the Attribute ID we'll use to output the Add to Cart button
// We'll create a meta field to that attribute whith a checkbox, to mark those attributes that will be returned by this function ;-)
function molswc_designated_addtocart_attributes() {
	$designated_addtocart_attributes = array('82');
	return $designated_addtocart_attributes;
}
// Exclude the products in these categories from displaying *on the shop page only*
add_action( 'woocommerce_product_query', 'molswc_custom_boards_query' );
function molswc_custom_boards_query( $q ) {
	if ( is_shop() ) {
		$raw_excluded_categories = strip_tags(get_option( 'molswc_excluded_categories' )); // get raw options as defined in options DB table
		$excluded_categories = explode(',', $raw_excluded_categories); // create an array with options
		$tax_query = (array) $q->get( 'tax_query' );
		$tax_query[] = array(
			   'taxonomy' => 'product_cat',
			   'field' => 'slug',
			   'terms' => $excluded_categories, 
			   'operator' => 'NOT IN'
		);
		$q->set( 'tax_query', $tax_query );
	}
}

// === Define and use custom dynamic colors in product page and product popup
// Register a product meta-box for defining custom buttons colors at product level
add_action( 'add_meta_boxes', 'my_custom_field_checkboxes' );
function my_custom_field_checkboxes() {
    add_meta_box(
        'molswc_custom_buttons_colors',          // this is HTML id of the box on edit screen
        'Custom Buttons colors',    // title of the box
        'molswc_custom_buttons_colors_content',   // function to be called to display the checkboxes, see the function below
        'product',        // on which edit screen the box should appear
        'normal',      // part of page where the box should appear
        'default'      // priority of the box
    );
}
// Display the previously registered metabox in Product Edit Screen
function molswc_custom_buttons_colors_content() {
    // nonce field for security check later on save
    wp_nonce_field( plugin_basename( __FILE__ ), 'mTdW34lFdE65' );
	// get the post and the current editing product ID
	global $post;
	$curr_prod_id = $post->ID;
	// get the Use Custom Colors condition out of the database
	$molswc_use_custom_button_colors_value = get_post_meta( $curr_prod_id, 'molswc_use_custom_button_colors' )[0];
	if ( $molswc_use_custom_button_colors_value == 1 ) { $molswc_use_custom_button_colors_checked = 'checked'; } else { $molswc_use_custom_button_colors_checked = ''; }
	// get the product container width units from database and set the "checked" variable that will be used later in form
	if( strip_tags(get_option( 'molswc_product_container_width_units' )) == 'px') { $molswc_product_container_width_default_units = '(default selected units is "px")'; }
	if( strip_tags(get_option( 'molswc_product_container_width_units' )) == '%') { $molswc_product_container_width_default_units = '(default selected units is "%")'; }
	// also set a message to display which one is default
	if( get_post_meta( $curr_prod_id, "molswc_product_container_width_units" )[0] == 'px' ) { $molswc_product_container_width_units_px = 'checked="checked"'; }
	if( get_post_meta( $curr_prod_id, "molswc_product_container_width_units" )[0] == '%' ) { $molswc_product_container_width_units_percent = 'checked="checked"'; }
	// output the attributes defining form (almost the same as in main/default plugin settings screen)
    echo '
	<p>
	<strong>How this works:</strong></br>
	Set the preferred attributes in the boxes below. If attributes are not set then the ones defined in the main plugin settings will be used (and displayed as placeholders).</br>
	Check the "Use custom attributes ..." option to use the attributes defined below; otherwise the default attributes will be used; still the values defined here will be preserved even if option is un-checked, maybe for later use.</br>
	For the single product page to be styled please add the class ".lithe_board_section" to the product section in Avia builder on that page (and any other page that needs custom attributes).
	</p>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Use custom attributes in this product: </th>
			<td colspan="4"> 
				<input type="checkbox" name="molswc_use_custom_button_colors" value="1" '.$molswc_use_custom_button_colors_checked.'/>
				<span>(check this to use attributes below; otherwise defaults will be applied)</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Product container width: </th>
			<td> 
				<input name="molswc_product_container_width" type="number" id="molswc_product_container_width" style="display: inline-block; width: auto;" aria-describedby="molswc_product_container_width" value="'.get_post_meta( $curr_prod_id, "molswc_product_container_width" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_container_width" )).'" class="regular-text">
			</td>
			<td colspan="5"> 
				<fieldset style="width:60px; display:inline-block;">
					<label><input name="molswc_product_container_width_units" type="radio" value="px"'.$molswc_product_container_width_units_px.'>px</label><br>
					<label><input name="molswc_product_container_width_units" type="radio" value="%"'.$molswc_product_container_width_units_percent.'>&percnt;</label>
				</fieldset>
				<span style="width:calc(100% - 65px); display:inline-block;">Both value and units must be set here; otherwise the defaults will be used instead.<br>'.$molswc_product_container_width_default_units.'</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Background color: </th>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<input name="molswc_product_background_color" type="text" id="molswc_product_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_product_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_product_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">IN STOCK button colors: </th>
			<td> 
				<span>Normal label color:</span>
				<input name="molswc_instock_label_color" type="text" id="molswc_instock_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover label color:</span>
				<input name="molswc_instock_label_hover_color" type="text" id="molswc_instock_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_label_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_label_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_label_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal background color:</span>
				<input name="molswc_instock_background_color" type="text" id="molswc_instock_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover background color:</span>
				<input name="molswc_instock_background_hover_color" type="text" id="molswc_instock_background_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_background_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_background_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_background_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_instock_border_color" type="text" id="molswc_instock_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_instock_border_hover_color" type="text" id="molswc_instock_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_border_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_instock_border_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_instock_border_hover_color" )).'" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">BACK ORDER button colors: </th>
			<td> 
				<span>Normal label color:</span>
				<input name="molswc_backorder_label_color" type="text" id="molswc_backorder_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover label color:</span>
				<input name="molswc_backorder_label_hover_color" type="text" id="molswc_backorder_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_label_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_label_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_label_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal background color:</span>
				<input name="molswc_backorder_background_color" type="text" id="molswc_backorder_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover background color:</span>
				<input name="molswc_backorder_background_hover_color" type="text" id="molswc_backorder_background_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_background_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_background_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_background_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_backorder_border_color" type="text" id="molswc_backorder_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_backorder_border_hover_color" type="text" id="molswc_backorder_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_border_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_backorder_border_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_backorder_border_hover_color" )).'" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">PRE ORDER button colors: </th>
			<td> 
				<span>Normal label color:</span>
				<input name="molswc_preorder_label_color" type="text" id="molswc_preorder_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover label color:</span>
				<input name="molswc_preorder_label_hover_color" type="text" id="molswc_preorder_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_label_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_label_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_label_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal background color:</span>
				<input name="molswc_preorder_background_color" type="text" id="molswc_preorder_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover background color:</span>
				<input name="molswc_preorder_background_hover_color" type="text" id="molswc_preorder_background_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_background_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_background_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_background_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_preorder_border_color" type="text" id="molswc_preorder_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_preorder_border_hover_color" type="text" id="molswc_preorder_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_border_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_preorder_border_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_preorder_border_hover_color" )).'" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">N/A button colors: </th>
			<td> 
				<span>Normal label color:</span>
				<input name="molswc_notavailable_label_color" type="text" id="molswc_notavailable_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover label color:</span>
				<input name="molswc_notavailable_label_hover_color" type="text" id="molswc_notavailable_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_label_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_label_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_label_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal background color:</span>
				<input name="molswc_notavailable_background_color" type="text" id="molswc_notavailable_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover background color:</span>
				<input name="molswc_notavailable_background_hover_color" type="text" id="molswc_notavailable_background_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_background_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_background_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_background_hover_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_notavailable_border_color" type="text" id="molswc_notavailable_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_notavailable_border_hover_color" type="text" id="molswc_notavailable_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_border_hover_color" value="'.get_post_meta( $curr_prod_id, "molswc_notavailable_border_hover_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_notavailable_border_hover_color" )).'" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Selected button colors: </th>
			<td> 
				<span style="display: block;">Label color:</span>
				<input name="molswc_selected_button_label_color" type="text" id="molswc_selected_button_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_selected_button_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_selected_button_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_selected_button_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			
			<td> 
				<span style="display: block;">Background color:</span>
				<input name="molswc_selected_button_background_color" type="text" id="molswc_selected_button_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_selected_button_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_selected_button_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_selected_button_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Border color:</span>
				<input name="molswc_selected_button_border_color" type="text" id="molswc_selected_button_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_selected_button_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_selected_button_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_selected_button_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Payment button colors: </th>
			<td> 
				<span style="display: block;">Title color:</span>
				<input name="molswc_payment_button_title_color" type="text" id="molswc_payment_button_title_color" style="display: inline-block; width: auto;" aria-describedby="molswc_payment_button_title_color" value="'.get_post_meta( $curr_prod_id, "molswc_payment_button_title_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_payment_button_title_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">Text color:</span>
				<input name="molswc_payment_button_text_color" type="text" id="molswc_payment_button_text_color" style="display: inline-block; width: auto;" aria-describedby="molswc_payment_button_text_color" value="'.get_post_meta( $curr_prod_id, "molswc_payment_button_text_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_payment_button_text_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">Background color:</span>
				<input name="molswc_payment_button_background_color" type="text" id="molswc_payment_button_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_payment_button_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_payment_button_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_payment_button_background_color" )).'" class="regular-text">
			</td>
			
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Border color:</span>
				<input name="molswc_payment_button_border_color" type="text" id="molswc_payment_button_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_payment_button_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_payment_button_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_payment_button_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Clear little button colors: </th>
			<td> 
				<span style="display: block;">Label color:</span>
				<input name="molswc_clear_button_label_color" type="text" id="molswc_clear_button_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_clear_button_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_clear_button_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_clear_button_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Background color:</span>
				<input name="molswc_clear_button_background_color" type="text" id="molswc_clear_button_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_clear_button_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_clear_button_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_clear_button_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Border color:</span>
				<input name="molswc_clear_button_border_color" type="text" id="molswc_clear_button_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_clear_button_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_clear_button_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_clear_button_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Learn More button colors: </th>
			<td> 
				<span style="display: block;">Label color:</span>
				<input name="molswc_learnmore_button_label_color" type="text" id="molswc_learnmore_button_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_learnmore_button_label_color" value="'.get_post_meta( $curr_prod_id, "molswc_learnmore_button_label_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_learnmore_button_label_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Background color:</span>
				<input name="molswc_learnmore_button_background_color" type="text" id="molswc_learnmore_button_background_color" style="display: inline-block; width: auto;" aria-describedby="molswc_learnmore_button_background_color" value="'.get_post_meta( $curr_prod_id, "molswc_learnmore_button_background_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_learnmore_button_background_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">Border color:</span>
				<input name="molswc_learnmore_button_border_color" type="text" id="molswc_learnmore_button_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_learnmore_button_border_color" value="'.get_post_meta( $curr_prod_id, "molswc_learnmore_button_border_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_learnmore_button_border_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Extra pop up colors: </th>
			<td> 
				<span style="display: block;">Product name color:</span>
				<input name="molswc_product_name_color" type="text" id="molswc_product_name_color" style="display: inline-block; width: auto;" aria-describedby="molswc_product_name_color" value="'.get_post_meta( $curr_prod_id, "molswc_product_name_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_name_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">Column title color:</span>
				<input name="molswc_column_title_color" type="text" id="molswc_column_title_color" style="display: inline-block; width: auto;" aria-describedby="molswc_column_title_color" value="'.get_post_meta( $curr_prod_id, "molswc_column_title_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_column_title_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">Column divider color:</span>
				<input name="molswc_column_divider_color" type="text" id="molswc_column_divider_color" style="display: inline-block; width: auto;" aria-describedby="molswc_column_divider_color" value="'.get_post_meta( $curr_prod_id, "molswc_column_divider_color" )[0].'" placeholder="'.strip_tags(get_option( "molswc_column_divider_color" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">Buttons border radius: <span style="display: block; font-weight: normal;">(always in PX)</span></th>
			<td> 
				<span>Product options radius:</span>
				<input name="molswc_product_options_button_radius" type="number" id="molswc_product_options_button_radius" style="display: inline-block; width: auto;" aria-describedby="molswc_product_options_button_radius" value="'.get_post_meta( $curr_prod_id, "molswc_product_options_button_radius" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_options_button_radius" )).'" class="regular-text">
			</td>
			<td> 
				<span>Buy Now button radius:</span>
				<input name="molswc_product_buynow_button_radius" type="number" id="molswc_product_buynow_button_radius" style="display: inline-block; width: auto;" aria-describedby="molswc_product_buynow_button_radius" value="'.get_post_meta( $curr_prod_id, "molswc_product_buynow_button_radius" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_buynow_button_radius" )).'" class="regular-text">
			</td>
			<td> 
				<span>Clear button radius:</span>
				<input name="molswc_product_clearoptions_button_radius" type="number" id="molswc_product_clearoptions_button_radius" style="display: inline-block; width: auto;" aria-describedby="molswc_product_clearoptions_button_radius" value="'.get_post_meta( $curr_prod_id, "molswc_product_clearoptions_button_radius" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_clearoptions_button_radius" )).'" class="regular-text">
			</td>
			<td> 
				<span>Learn More button radius:</span>
				<input name="molswc_product_learnmore_button_radius" type="number" id="molswc_product_learnmore_button_radius" style="display: inline-block; width: auto;" aria-describedby="molswc_product_learnmore_button_radius" value="'.get_post_meta( $curr_prod_id, "molswc_product_learnmore_button_radius" )[0].'" placeholder="'.strip_tags(get_option( "molswc_product_learnmore_button_radius" )).'" class="regular-text">
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
			<td> 
				<span style="display: block;">&nbsp;</span>
			</td>
		</tr>
	</table>
	';
}
// Save metabox data at product update
add_action( 'woocommerce_update_product', 'molswc_custom_field_data' );
function molswc_custom_field_data($curr_prod_id) {
    // check some conditions before just saving the fields
	if ( !current_user_can('manage_woocommerce') ) { return; }
    if ( !wp_verify_nonce( $_POST['mTdW34lFdE65'], plugin_basename( __FILE__ ) ) ) { return; }
	// first store checkbox state
	if ( isset( $_POST['molswc_use_custom_button_colors'] ) ) {
        update_post_meta( $curr_prod_id, 'molswc_use_custom_button_colors', 1 );
    } else {
        update_post_meta( $curr_prod_id, 'molswc_use_custom_button_colors', 0 );
	}
    // now store colors data in custom post meta based on field values 
    if ( isset( $_POST['molswc_product_background_color'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_product_background_color', strip_tags($_POST['molswc_product_background_color']) ); }
	if ( isset( $_POST['molswc_instock_label_color'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_instock_label_color', strip_tags($_POST['molswc_instock_label_color']) ); }
	if ( isset( $_POST['molswc_instock_label_hover_color'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_instock_label_hover_color', strip_tags($_POST['molswc_instock_label_hover_color']) ); }
	if ( isset( $_POST['molswc_instock_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_instock_border_color', strip_tags($_POST['molswc_instock_border_color']) ); }
	if ( isset( $_POST['molswc_instock_border_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_instock_border_hover_color', strip_tags($_POST['molswc_instock_border_hover_color']) ); }
	if ( isset( $_POST['molswc_backorder_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_label_color', strip_tags($_POST['molswc_backorder_label_color']) ); }
	if ( isset( $_POST['molswc_backorder_label_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_label_hover_color', strip_tags($_POST['molswc_backorder_label_hover_color']) ); }
	if ( isset( $_POST['molswc_backorder_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_border_color', strip_tags($_POST['molswc_backorder_border_color']) ); }
	if ( isset( $_POST['molswc_backorder_border_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_border_hover_color', strip_tags($_POST['molswc_backorder_border_hover_color']) ); }
	if ( isset( $_POST['molswc_preorder_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_label_color', strip_tags($_POST['molswc_preorder_label_color']) ); }
	if ( isset( $_POST['molswc_preorder_label_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_label_hover_color', strip_tags($_POST['molswc_preorder_label_hover_color']) ); }
	if ( isset( $_POST['molswc_preorder_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_border_color', strip_tags($_POST['molswc_preorder_border_color']) ); }
	if ( isset( $_POST['molswc_preorder_border_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_border_hover_color', strip_tags($_POST['molswc_preorder_border_hover_color']) ); }
	if ( isset( $_POST['molswc_notavailable_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_label_color', strip_tags($_POST['molswc_notavailable_label_color']) ); }
	if ( isset( $_POST['molswc_notavailable_label_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_label_hover_color', strip_tags($_POST['molswc_notavailable_label_hover_color']) ); }
	if ( isset( $_POST['molswc_notavailable_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_border_color', strip_tags($_POST['molswc_notavailable_border_color']) ); }
	if ( isset( $_POST['molswc_notavailable_border_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_border_hover_color', strip_tags($_POST['molswc_notavailable_border_hover_color']) ); }
	if ( isset( $_POST['molswc_selected_button_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_selected_button_label_color', strip_tags($_POST['molswc_selected_button_label_color']) ); }
	if ( isset( $_POST['molswc_selected_button_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_selected_button_border_color', strip_tags($_POST['molswc_selected_button_border_color']) ); }
	if ( isset( $_POST['molswc_payment_button_title_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_payment_button_title_color', strip_tags($_POST['molswc_payment_button_title_color']) ); }
	if ( isset( $_POST['molswc_payment_button_text_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_payment_button_text_color', strip_tags($_POST['molswc_payment_button_text_color']) ); }
	if ( isset( $_POST['molswc_payment_button_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_payment_button_border_color', strip_tags($_POST['molswc_payment_button_border_color']) ); }
	if ( isset( $_POST['molswc_clear_button_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_clear_button_label_color', strip_tags($_POST['molswc_clear_button_label_color']) ); }
	if ( isset( $_POST['molswc_clear_button_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_clear_button_border_color', strip_tags($_POST['molswc_clear_button_border_color']) ); }
	if ( isset( $_POST['molswc_learnmore_button_label_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_learnmore_button_label_color', strip_tags($_POST['molswc_learnmore_button_label_color']) ); }
	if ( isset( $_POST['molswc_learnmore_button_border_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_learnmore_button_border_color', strip_tags($_POST['molswc_learnmore_button_border_color']) ); }
	if ( isset( $_POST['molswc_instock_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_instock_background_color', strip_tags($_POST['molswc_instock_background_color']) ); }
	if ( isset( $_POST['molswc_instock_background_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_instock_background_hover_color', strip_tags($_POST['molswc_instock_background_hover_color']) ); }
	if ( isset( $_POST['molswc_backorder_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_background_color', strip_tags($_POST['molswc_backorder_background_color']) ); }
	if ( isset( $_POST['molswc_backorder_background_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_backorder_background_hover_color', strip_tags($_POST['molswc_backorder_background_hover_color']) ); }
	if ( isset( $_POST['molswc_preorder_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_background_color', strip_tags($_POST['molswc_preorder_background_color']) ); }
	if ( isset( $_POST['molswc_preorder_background_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_preorder_background_hover_color', strip_tags($_POST['molswc_preorder_background_hover_color']) ); }
	if ( isset( $_POST['molswc_notavailable_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_background_color', strip_tags($_POST['molswc_notavailable_background_color']) ); }
	if ( isset( $_POST['molswc_notavailable_background_hover_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_notavailable_background_hover_color', strip_tags($_POST['molswc_notavailable_background_hover_color']) ); }
	if ( isset( $_POST['molswc_selected_button_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_selected_button_background_color', strip_tags($_POST['molswc_selected_button_background_color']) ); }
	if ( isset( $_POST['molswc_payment_button_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_payment_button_background_color', strip_tags($_POST['molswc_payment_button_background_color']) ); }
	if ( isset( $_POST['molswc_clear_button_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_clear_button_background_color', strip_tags($_POST['molswc_clear_button_background_color']) ); }
	if ( isset( $_POST['molswc_learnmore_button_background_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_learnmore_button_background_color', strip_tags($_POST['molswc_learnmore_button_background_color']) ); }
	if ( isset( $_POST['molswc_product_name_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_name_color', strip_tags($_POST['molswc_product_name_color']) ); }
	if ( isset( $_POST['molswc_column_title_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_column_title_color', strip_tags($_POST['molswc_column_title_color']) ); }
	if ( isset( $_POST['molswc_column_divider_color'] ) ) { update_post_meta( $curr_prod_id, 'molswc_column_divider_color', strip_tags($_POST['molswc_column_divider_color']) ); }
	if ( isset( $_POST['molswc_product_container_width'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_container_width', strip_tags($_POST['molswc_product_container_width']) ); }
	if ( isset( $_POST['molswc_product_container_width_units'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_container_width_units', strip_tags($_POST['molswc_product_container_width_units']) ); }
	if ( isset( $_POST['molswc_product_options_button_radius'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_options_button_radius', strip_tags($_POST['molswc_product_options_button_radius']) ); }
	if ( isset( $_POST['molswc_product_buynow_button_radius'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_buynow_button_radius', strip_tags($_POST['molswc_product_buynow_button_radius']) ); }
	if ( isset( $_POST['molswc_product_clearoptions_button_radius'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_clearoptions_button_radius', strip_tags($_POST['molswc_product_clearoptions_button_radius']) ); }
	if ( isset( $_POST['molswc_product_learnmore_button_radius'] ) ) { update_post_meta( $curr_prod_id, 'molswc_product_learnmore_button_radius', strip_tags($_POST['molswc_product_learnmore_button_radius']) ); }
}
// Output custom product colors styles as defined in Product Edit screen *or* Plugin Options page, depending on the individual product option
add_action( 'wp_head', 'molswc_styles_for_custom_product_colors', 99999 );
function molswc_styles_for_custom_product_colors() {
	// Let's see where we are first (product or not)
	if ( is_product() ) {
		// In product we only need currently displayed product CSS, so get its ID and move on
		global $post;
		$displayed_ids[0] = $post->ID;		
	} else {
		// For the other pages we don't know what will be displayed, so get the complete products list 
		$displayed_ids = get_posts( array(
			'post_type' => 'product',
			'numberposts' => -1,
			'post_status' => 'publish',
			'fields' => 'ids',
		) );
	}
	// Start outputting the <style> block
	echo '
	<!-- Lithe custom product colors START -->
	<style type="text/css">'; 
	// Now take each displayed product one by one
	foreach ($displayed_ids as $displayed_id) {
		// first let's see if custom colors is selected for the current product; skip everything otherwise, it's more efficient this way ;-)  
		$molswc_use_custom_button_colors_value = get_post_meta( $displayed_id, 'molswc_use_custom_button_colors' )[0];
		// then, based on the above, decide if we should extract and use the custom colors - for each type of stock type and each style individually
		if ( $molswc_use_custom_button_colors_value == 1 ) {
			if ( get_post_meta ($displayed_id, 'molswc_product_background_color')[0] ) { $curr_prod_background_color = get_post_meta ($displayed_id, 'molswc_product_background_color')[0]; } else { $curr_prod_background_color = strip_tags(get_option( 'molswc_product_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_border_color')[0] ) { $molswc_instock_border_color = get_post_meta ($displayed_id, 'molswc_instock_border_color')[0]; } else { $molswc_instock_border_color = strip_tags(get_option( 'molswc_instock_border_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_label_color')[0] ) { $molswc_instock_label_color = get_post_meta ($displayed_id, 'molswc_instock_label_color')[0]; } else { $molswc_instock_label_color = strip_tags(get_option( 'molswc_instock_label_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_border_hover_color')[0] ) { $molswc_instock_border_hover_color = get_post_meta ($displayed_id, 'molswc_instock_border_hover_color')[0]; } else { $molswc_instock_border_hover_color = strip_tags(get_option( 'molswc_instock_border_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_label_hover_color')[0] ) { $molswc_instock_label_hover_color = get_post_meta ($displayed_id, 'molswc_instock_label_hover_color')[0]; } else { $molswc_instock_label_hover_color = strip_tags(get_option( 'molswc_instock_label_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_backorder_border_color')[0] ) { $molswc_backorder_border_color = get_post_meta ($displayed_id, 'molswc_backorder_border_color')[0]; } else { $molswc_backorder_border_color = strip_tags(get_option( 'molswc_backorder_border_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_backorder_label_color')[0] ) { $molswc_backorder_label_color = get_post_meta ($displayed_id, 'molswc_backorder_label_color')[0]; } else { $molswc_backorder_label_color = strip_tags(get_option( 'molswc_backorder_label_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_backorder_border_hover_color')[0] ) { $molswc_backorder_border_hover_color = get_post_meta ($displayed_id, 'molswc_backorder_border_hover_color')[0]; } else { $molswc_backorder_border_hover_color = strip_tags(get_option( 'molswc_backorder_border_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_backorder_label_hover_color')[0] ) { $molswc_backorder_label_hover_color = get_post_meta ($displayed_id, 'molswc_backorder_label_hover_color')[0]; } else { $molswc_backorder_label_hover_color = strip_tags(get_option( 'molswc_backorder_label_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_preorder_border_color')[0] ) { $molswc_preorder_border_color = get_post_meta ($displayed_id, 'molswc_preorder_border_color')[0]; } else { $molswc_preorder_border_color = strip_tags(get_option( 'molswc_preorder_border_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_preorder_label_color')[0] ) { $molswc_preorder_label_color = get_post_meta ($displayed_id, 'molswc_preorder_label_color')[0]; } else { $molswc_preorder_label_color = strip_tags(get_option( 'molswc_preorder_label_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_preorder_border_hover_color')[0] ) { $molswc_preorder_border_hover_color = get_post_meta ($displayed_id, 'molswc_preorder_border_hover_color')[0]; } else { $molswc_preorder_border_hover_color = strip_tags(get_option( 'molswc_preorder_border_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_preorder_label_hover_color')[0] ) { $molswc_preorder_label_hover_color = get_post_meta ($displayed_id, 'molswc_preorder_label_hover_color')[0]; } else { $molswc_preorder_label_hover_color = strip_tags(get_option( 'molswc_preorder_label_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_border_color')[0] ) { $molswc_notavailable_border_color = get_post_meta ($displayed_id, 'molswc_notavailable_border_color')[0]; } else { $molswc_notavailable_border_color = strip_tags(get_option( 'molswc_notavailable_border_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_label_color')[0] ) { $molswc_notavailable_label_color = get_post_meta ($displayed_id, 'molswc_notavailable_label_color')[0]; } else { $molswc_notavailable_label_color = strip_tags(get_option( 'molswc_notavailable_label_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_border_hover_color')[0] ) { $molswc_notavailable_border_hover_color = get_post_meta ($displayed_id, 'molswc_notavailable_border_hover_color')[0]; } else { $molswc_notavailable_border_hover_color = strip_tags(get_option( 'molswc_notavailable_border_hover_color' )); } 
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_label_hover_color')[0] ) { $molswc_notavailable_label_hover_color = get_post_meta ($displayed_id, 'molswc_notavailable_label_hover_color')[0]; } else { $molswc_notavailable_label_hover_color = strip_tags(get_option( 'molswc_notavailable_label_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_selected_button_label_color')[0] ) { $molswc_selected_button_label_color = get_post_meta ($displayed_id, 'molswc_selected_button_label_color')[0]; } else { $molswc_selected_button_label_color = strip_tags(get_option( 'molswc_selected_button_label_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_selected_button_border_color')[0] ) { $molswc_selected_button_border_color = get_post_meta ($displayed_id, 'molswc_selected_button_border_color')[0]; } else { $molswc_selected_button_border_color = strip_tags(get_option( 'molswc_selected_button_border_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_payment_button_title_color')[0] ) { $molswc_payment_button_title_color = get_post_meta ($displayed_id, 'molswc_payment_button_title_color')[0]; } else { $molswc_payment_button_title_color = strip_tags(get_option( 'molswc_payment_button_title_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_payment_button_text_color')[0] ) { $molswc_payment_button_text_color = get_post_meta ($displayed_id, 'molswc_payment_button_text_color')[0]; } else { $molswc_payment_button_text_color = strip_tags(get_option( 'molswc_payment_button_text_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_payment_button_border_color')[0] ) { $molswc_payment_button_border_color = get_post_meta ($displayed_id, 'molswc_payment_button_border_color')[0]; } else { $molswc_payment_button_border_color = strip_tags(get_option( 'molswc_payment_button_border_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_clear_button_label_color')[0] ) { $molswc_clear_button_label_color = get_post_meta ($displayed_id, 'molswc_clear_button_label_color')[0]; } else { $molswc_clear_button_label_color = strip_tags(get_option( 'molswc_clear_button_label_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_clear_button_border_color')[0] ) { $molswc_clear_button_border_color = get_post_meta ($displayed_id, 'molswc_clear_button_border_color')[0]; } else { $molswc_clear_button_border_color = strip_tags(get_option( 'molswc_clear_button_border_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_learnmore_button_label_color')[0] ) { $molswc_learnmore_button_label_color = get_post_meta ($displayed_id, 'molswc_learnmore_button_label_color')[0]; } else { $molswc_learnmore_button_label_color = strip_tags(get_option( 'molswc_learnmore_button_label_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_learnmore_button_border_color')[0] ) { $molswc_learnmore_button_border_color = get_post_meta ($displayed_id, 'molswc_learnmore_button_border_color')[0]; } else { $molswc_learnmore_button_border_color = strip_tags(get_option( 'molswc_learnmore_button_border_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_product_name_color')[0] ) { $molswc_product_name_color = get_post_meta ($displayed_id, 'molswc_product_name_color')[0]; } else { $molswc_product_name_color = strip_tags(get_option( 'molswc_product_name_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_column_title_color')[0] ) { $molswc_column_title_color = get_post_meta ($displayed_id, 'molswc_column_title_color')[0]; } else { $molswc_column_title_color = strip_tags(get_option( 'molswc_column_title_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_column_divider_color')[0] ) { $molswc_column_divider_color = get_post_meta ($displayed_id, 'molswc_column_divider_color')[0]; } else { $molswc_column_divider_color = strip_tags(get_option( 'molswc_column_divider_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_background_color')[0] ) { $molswc_instock_background_color = get_post_meta ($displayed_id, 'molswc_instock_background_color')[0]; } else { $molswc_instock_background_color = strip_tags(get_option( 'molswc_instock_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_instock_background_hover_color')[0] ) { $molswc_instock_background_hover_color = get_post_meta ($displayed_id, 'molswc_instock_background_hover_color')[0]; } else { $molswc_instock_background_hover_color = strip_tags(get_option( 'molswc_instock_background_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_backorder_background_color')[0] ) { $molswc_backorder_background_color = get_post_meta ($displayed_id, 'molswc_backorder_background_color')[0]; } else { $molswc_backorder_background_color = strip_tags(get_option( 'molswc_backorder_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_backorder_background_hover_color')[0] ) { $molswc_backorder_background_hover_color = get_post_meta ($displayed_id, 'molswc_backorder_background_hover_color')[0]; } else { $molswc_backorder_background_hover_color = strip_tags(get_option( 'molswc_backorder_background_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_preorder_background_color')[0] ) { $molswc_preorder_background_color = get_post_meta ($displayed_id, 'molswc_preorder_background_color')[0]; } else { $molswc_preorder_background_color = strip_tags(get_option( 'molswc_preorder_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_preorder_background_hover_color')[0] ) { $molswc_preorder_background_hover_color = get_post_meta ($displayed_id, 'molswc_preorder_background_hover_color')[0]; } else { $molswc_preorder_background_hover_color = strip_tags(get_option( 'molswc_preorder_background_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_background_color')[0] ) { $molswc_notavailable_background_color = get_post_meta ($displayed_id, 'molswc_notavailable_background_color')[0]; } else { $molswc_notavailable_background_color = strip_tags(get_option( 'molswc_notavailable_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_notavailable_background_hover_color')[0] ) { $molswc_notavailable_background_hover_color = get_post_meta ($displayed_id, 'molswc_notavailable_background_hover_color')[0]; } else { $molswc_notavailable_background_hover_color = strip_tags(get_option( 'molswc_notavailable_background_hover_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_selected_button_background_color')[0] ) { $molswc_selected_button_background_color = get_post_meta ($displayed_id, 'molswc_selected_button_background_color')[0]; } else { $molswc_selected_button_background_color = strip_tags(get_option( 'molswc_selected_button_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_payment_button_background_color')[0] ) { $molswc_payment_button_background_color = get_post_meta ($displayed_id, 'molswc_payment_button_background_color')[0]; } else { $molswc_payment_button_background_color = strip_tags(get_option( 'molswc_payment_button_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_clear_button_background_color')[0] ) { $molswc_clear_button_background_color = get_post_meta ($displayed_id, 'molswc_clear_button_background_color')[0]; } else { $molswc_clear_button_background_color = strip_tags(get_option( 'molswc_clear_button_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_learnmore_button_background_color')[0] ) { $molswc_learnmore_button_background_color = get_post_meta ($displayed_id, 'molswc_learnmore_button_background_color')[0]; } else { $molswc_learnmore_button_background_color = strip_tags(get_option( 'molswc_learnmore_button_background_color' )); }
			if ( get_post_meta ($displayed_id, 'molswc_product_options_button_radius')[0] ) { $molswc_product_options_button_radius = get_post_meta ($displayed_id, 'molswc_product_options_button_radius')[0]; } else { $molswc_product_options_button_radius = strip_tags(get_option( 'molswc_product_options_button_radius' )); }
			if ( get_post_meta ($displayed_id, 'molswc_product_buynow_button_radius')[0] ) { $molswc_product_buynow_button_radius = get_post_meta ($displayed_id, 'molswc_product_buynow_button_radius')[0]; } else { $molswc_product_buynow_button_radius = strip_tags(get_option( 'molswc_product_buynow_button_radius' )); }
			if ( get_post_meta ($displayed_id, 'molswc_product_clearoptions_button_radius')[0] ) { $molswc_product_clearoptions_button_radius = get_post_meta ($displayed_id, 'molswc_product_clearoptions_button_radius')[0]; } else { $molswc_product_clearoptions_button_radius = strip_tags(get_option( 'molswc_product_clearoptions_button_radius' )); }
			if ( get_post_meta ($displayed_id, 'molswc_product_learnmore_button_radius')[0] ) { $molswc_product_learnmore_button_radius = get_post_meta ($displayed_id, 'molswc_product_learnmore_button_radius')[0]; } else { $molswc_product_learnmore_button_radius = strip_tags(get_option( 'molswc_product_learnmore_button_radius' )); }
			// container width and its units are a special case: both value and units must be set, otherwise we'll use defaults - this is to avoid situations like having set 90% in defaults and overriding the percent with "px" locally, resulting in a 90 pixels wide container
			// so we IF them both at once and set the values for both of them accordingly
			if ( get_post_meta ($displayed_id, 'molswc_product_container_width')[0] && get_post_meta ($displayed_id, 'molswc_product_container_width_units')[0] ) { 
				$molswc_product_container_width = get_post_meta ($displayed_id, 'molswc_product_container_width')[0]; 
				$molswc_product_container_width_units = get_post_meta ($displayed_id, 'molswc_product_container_width_units')[0];
			} else { 
				$molswc_product_container_width = strip_tags(get_option( 'molswc_product_container_width' )); 
				$molswc_product_container_width_units = strip_tags(get_option( 'molswc_product_container_width_units' ));
			}
		} else {
			// if the custom colors checkbox is not checked fall back on the defaults
			$curr_prod_background_color = strip_tags(get_option( 'molswc_product_background_color' ));
			$molswc_instock_border_color = strip_tags(get_option( 'molswc_instock_border_color' ));
			$molswc_instock_label_color = strip_tags(get_option( 'molswc_instock_label_color' ));
			$molswc_instock_border_hover_color = strip_tags(get_option( 'molswc_instock_border_hover_color' ));
			$molswc_instock_label_hover_color = strip_tags(get_option( 'molswc_instock_label_hover_color' ));
			$molswc_backorder_border_color = strip_tags(get_option( 'molswc_backorder_border_color' ));
			$molswc_backorder_label_color = strip_tags(get_option( 'molswc_backorder_label_color' ));
			$molswc_backorder_border_hover_color = strip_tags(get_option( 'molswc_backorder_border_hover_color' ));
			$molswc_backorder_label_hover_color = strip_tags(get_option( 'molswc_backorder_label_hover_color' ));
			$molswc_preorder_border_color = strip_tags(get_option( 'molswc_preorder_border_color' ));
			$molswc_preorder_label_color = strip_tags(get_option( 'molswc_preorder_label_color' ));
			$molswc_preorder_border_hover_color = strip_tags(get_option( 'molswc_preorder_border_hover_color' ));
			$molswc_preorder_label_hover_color = strip_tags(get_option( 'molswc_preorder_label_hover_color' ));
			$molswc_notavailable_border_color = strip_tags(get_option( 'molswc_notavailable_border_color' ));
			$molswc_notavailable_label_color = strip_tags(get_option( 'molswc_notavailable_label_color' ));
			$molswc_notavailable_border_hover_color = strip_tags(get_option( 'molswc_notavailable_border_hover_color' ));
			$molswc_notavailable_label_hover_color = strip_tags(get_option( 'molswc_notavailable_label_hover_color' ));
			$molswc_selected_button_label_color = strip_tags(get_option( 'molswc_selected_button_label_color' ));
			$molswc_selected_button_border_color = strip_tags(get_option( 'molswc_selected_button_border_color' ));
			$molswc_payment_button_title_color = strip_tags(get_option( 'molswc_payment_button_title_color' ));
			$molswc_payment_button_text_color = strip_tags(get_option( 'molswc_payment_button_text_color' ));
			$molswc_payment_button_border_color = strip_tags(get_option( 'molswc_payment_button_border_color' ));
			$molswc_clear_button_label_color = strip_tags(get_option( 'molswc_clear_button_label_color' ));
			$molswc_clear_button_border_color = strip_tags(get_option( 'molswc_clear_button_border_color' ));
			$molswc_learnmore_button_label_color = strip_tags(get_option( 'molswc_learnmore_button_label_color' ));
			$molswc_learnmore_button_border_color = strip_tags(get_option( 'molswc_learnmore_button_border_color' ));
			$molswc_product_name_color = strip_tags(get_option( 'molswc_product_name_color' ));
			$molswc_column_title_color = strip_tags(get_option( 'molswc_column_title_color' ));
			$molswc_column_divider_color = strip_tags(get_option( 'molswc_column_divider_color' ));
			$molswc_instock_background_color = strip_tags(get_option( 'molswc_instock_background_color' ));
			$molswc_instock_background_hover_color = strip_tags(get_option( 'molswc_instock_background_hover_color' ));
			$molswc_backorder_background_color = strip_tags(get_option( 'molswc_backorder_background_color' ));
			$molswc_backorder_background_hover_color = strip_tags(get_option( 'molswc_backorder_background_hover_color' ));
			$molswc_preorder_background_color = strip_tags(get_option( 'molswc_preorder_background_color' ));
			$molswc_preorder_background_hover_color = strip_tags(get_option( 'molswc_preorder_background_hover_color' ));
			$molswc_notavailable_background_color = strip_tags(get_option( 'molswc_notavailable_background_color' ));
			$molswc_notavailable_background_hover_color = strip_tags(get_option( 'molswc_notavailable_background_hover_color' ));
			$molswc_selected_button_background_color = strip_tags(get_option( 'molswc_selected_button_background_color' ));
			$molswc_payment_button_background_color = strip_tags(get_option( 'molswc_payment_button_background_color' ));
			$molswc_clear_button_background_color = strip_tags(get_option( 'molswc_clear_button_background_color' ));
			$molswc_learnmore_button_background_color = strip_tags(get_option( 'molswc_learnmore_button_background_color' ));
			$molswc_product_options_button_radius = strip_tags(get_option( 'molswc_product_options_button_radius' ));
			$molswc_product_buynow_button_radius = strip_tags(get_option( 'molswc_product_buynow_button_radius' ));
			$molswc_product_clearoptions_button_radius = strip_tags(get_option( 'molswc_product_clearoptions_button_radius' ));
			$molswc_product_learnmore_button_radius = strip_tags(get_option( 'molswc_product_learnmore_button_radius' ));
			$molswc_product_container_width = strip_tags(get_option( 'molswc_product_container_width' ));
			$molswc_product_container_width_units = strip_tags(get_option( 'molswc_product_container_width_units' ));
		}
		// finally echo the CSS styles for the current product custom colors based on the stored variables and current product ID
		// if IT IS PRODUCT then we'll style the product section - that needs to have ".lithe_board_section" class added, otherwise ... no styling (so we can preserve original/other styles for non-board products)
		if ( is_product() ) {
			echo "
			/* Product: ".$displayed_id.", custom option: ".$molswc_use_custom_button_colors_value." */
			/* The background */
			body.postid-".$displayed_id." .lithe_board_section { background-color: ".$curr_prod_background_color."; }
			/* Border Radius (Learn More not available here in Single Product) */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib { border-radius: ".$molswc_product_options_button_radius."px !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > .button_wrapper { border-radius: ".$molswc_product_buynow_button_radius."px !important; }
			body.postid-".$displayed_id." .lithe_board_section a.reset_variations { border-radius: ".$molswc_product_clearoptions_button_radius."px !important; }			
			/* In Stock */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked) { border-color: ".$molswc_instock_border_color." !important; background: ".$molswc_instock_background_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked) .inner-attrib { color: ".$molswc_instock_label_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked):hover { border-color: ".$molswc_instock_border_hover_color." !important; background: ".$molswc_instock_background_hover_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked):hover .inner-attrib { color: ".$molswc_instock_label_hover_color." !important; }
			/* Back Order */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked) { border-color: ".$molswc_backorder_border_color." !important; background: ".$molswc_backorder_background_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked) .inner-attrib { color: ".$molswc_backorder_label_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked):hover { border-color: ".$molswc_backorder_border_hover_color." !important; background: ".$molswc_backorder_background_hover_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked):hover .inner-attrib { color: ".$molswc_backorder_label_hover_color." !important; }
			/* Pre Order */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked) { border-color: ".$molswc_preorder_border_color." !important; background: ".$molswc_preorder_background_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked) .inner-attrib { color: ".$molswc_preorder_label_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked):hover { border-color: ".$molswc_preorder_border_hover_color." !important; background: ".$molswc_preorder_background_hover_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked):hover .inner-attrib { color: ".$molswc_preorder_label_hover_color." !important; }
			/* Not Available */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked) { border-color: ".$molswc_notavailable_border_color." !important; background: ".$molswc_notavailable_background_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked) .inner-attrib { color: ".$molswc_notavailable_label_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked):hover { border-color: ".$molswc_notavailable_border_hover_color." !important; background: ".$molswc_notavailable_background_hover_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked):hover .inner-attrib { color: ".$molswc_notavailable_label_hover_color." !important; }
			/* Selected */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.radio-checked { border-color: ".$molswc_selected_button_border_color." !important; background: ".$molswc_selected_button_background_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td div.attrib.radio-checked .inner-attrib { color: ".$molswc_selected_button_label_color." !important; }
			/* Payment buttons */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > .button_wrapper > label { color: ".$molswc_payment_button_title_color." !important; } /* Title */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > span.attribPrice { color: ".$molswc_payment_button_text_color." !important; } /* Price */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > span.attribStockStatus { color: ".$molswc_payment_button_text_color." !important; } /* Stock */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > .button_wrapper > span.attrib-description { color: ".$molswc_payment_button_text_color." !important; } /* Description */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td > .buying.tax > .button_wrapper { border-color: ".$molswc_payment_button_border_color." !important; background: ".$molswc_payment_button_background_color." !important; } /* Border */
			/* Clear little button */
			body.postid-".$displayed_id." .lithe_board_section a.reset_variations { color: ".$molswc_clear_button_label_color." !important; } 
			body.postid-".$displayed_id." .lithe_board_section a.reset_variations:active { color: ".$molswc_clear_button_label_color." !important; } 
			body.postid-".$displayed_id." .lithe_board_section a.reset_variations { border-color: ".$molswc_clear_button_border_color." !important; background: ".$molswc_clear_button_background_color." !important; } 
			body.postid-".$displayed_id." .lithe_board_section a.reset_variations:active { border-color: ".$molswc_clear_button_border_color." !important; background: ".$molswc_clear_button_background_color." !important; } 
			/* Product name (title) */
			body.postid-".$displayed_id." .lithe_board_section h1 { color: ".$molswc_product_name_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section h2 { color: ".$molswc_product_name_color." !important; }
			/* Main column title */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody label.product_column_title { color: ".$molswc_column_title_color." !important; }
			/* Column titles */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .tr .each-attrib .label.td label { color: ".$molswc_column_title_color." !important; }
			/* Column dividers */
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td .each-attrib:not(:first-child) { border-left-color: ".$molswc_column_divider_color." !important; }
			body.postid-".$displayed_id." .lithe_board_section .table.variations .tbody .value.td .each-attrib .label.td { border-bottom-color: ".$molswc_column_divider_color." !important; }
			/* Container width */
			body.postid-".$displayed_id." .lithe_board_section > .container { width: ".$molswc_product_container_width.$molswc_product_container_width_units." !important; }
			";
		} else {
			// but IF IT'S NOT PRODUCT then only the xoo popup needs styling ...
			echo "
			/* Product: ".$displayed_id.", custom option: ".$molswc_use_custom_button_colors_value." */
			/* The background */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product { background-color: ".$curr_prod_background_color."; }
			/* Border Radius */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib { border-radius: ".$molswc_product_options_button_radius."px !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > .button_wrapper { border-radius: ".$molswc_product_buynow_button_radius."px !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product a.reset_variations { border-radius: ".$molswc_product_clearoptions_button_radius."px !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .xoo-qv-plink { border-radius: ".$molswc_product_learnmore_button_radius."px !important; }
			/* In Stock */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked) { border-color: ".$molswc_instock_border_color." !important; background: ".$molswc_instock_background_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked) .inner-attrib { color: ".$molswc_instock_label_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked):hover { border-color: ".$molswc_instock_border_hover_color." !important; background: ".$molswc_instock_background_hover_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_instock:not(.radio-checked):hover .inner-attrib { color: ".$molswc_instock_label_hover_color." !important; }
			/* Back Order */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked) { border-color: ".$molswc_backorder_border_color." !important; background: ".$molswc_backorder_background_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked) .inner-attrib { color: ".$molswc_backorder_label_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked):hover { border-color: ".$molswc_backorder_border_hover_color." !important; background: ".$molswc_backorder_background_hover_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_backorder:not(.radio-checked):hover .inner-attrib { color: ".$molswc_backorder_label_hover_color." !important; }
			/* Pre Order */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked) { border-color: ".$molswc_preorder_border_color." !important; background: ".$molswc_preorder_background_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked) .inner-attrib { color: ".$molswc_preorder_label_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked):hover { border-color: ".$molswc_preorder_border_hover_color." !important; background: ".$molswc_preorder_background_hover_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_preorder:not(.radio-checked):hover .inner-attrib { color: ".$molswc_preorder_label_hover_color." !important; }
			/* Not Available */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked) { border-color: ".$molswc_notavailable_border_color." !important; background: ".$molswc_notavailable_background_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked) .inner-attrib { color: ".$molswc_notavailable_label_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked):hover { border-color: ".$molswc_notavailable_border_hover_color." !important; background: ".$molswc_notavailable_background_hover_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.var_stock_not_available:not(.radio-checked):hover .inner-attrib { color: ".$molswc_notavailable_label_hover_color." !important; }
			/* Selected */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.radio-checked { border-color: ".$molswc_selected_button_border_color." !important; background: ".$molswc_selected_button_background_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td div.attrib.radio-checked .inner-attrib { color: ".$molswc_selected_button_label_color." !important; }
			/* Payment buttons */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > .button_wrapper > label { color: ".$molswc_payment_button_title_color." !important; } /* Title */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > span.attribPrice { color: ".$molswc_payment_button_text_color." !important; } /* Price */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > span.attribStockStatus { color: ".$molswc_payment_button_text_color." !important; } /* Stock */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > .button_wrapper > span.attrib-description { color: ".$molswc_payment_button_text_color." !important; } /* Description */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td > .buying.tax > .button_wrapper { border-color: ".$molswc_payment_button_border_color." !important; background: ".$molswc_payment_button_background_color." !important; } /* Border */
			/* Clear little button */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product a.reset_variations { color: ".$molswc_clear_button_label_color." !important; } 
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product a.reset_variations:active { color: ".$molswc_clear_button_label_color." !important; } 
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product a.reset_variations { border-color: ".$molswc_clear_button_border_color." !important; background: ".$molswc_clear_button_background_color." !important; } 
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product a.reset_variations:active { border-color: ".$molswc_clear_button_border_color." !important; background: ".$molswc_clear_button_background_color." !important; } 
			/* Learn More */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .xoo-qv-plink a { color: ".$molswc_learnmore_button_label_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .xoo-qv-plink { border-color: ".$molswc_learnmore_button_border_color." !important; background: ".$molswc_learnmore_button_background_color." !important; }
			/* Product name (title) */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product h1.product_title { color: ".$molswc_product_name_color." !important; }
			/* Main column title */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody label.product_column_title { color: ".$molswc_column_title_color." !important; }
			/* Column titles */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .tr .each-attrib .label.td label { color: ".$molswc_column_title_color." !important; }
			/* Column dividers */
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td .each-attrib:not(:first-child) { border-left-color: ".$molswc_column_divider_color." !important; }
			.xoo-qv-container .xoo-qv-main > div.product.post-".$displayed_id.".type-product .table.variations .tbody .value.td .each-attrib .label.td { border-bottom-color: ".$molswc_column_divider_color." !important; }
			";
		}
	}
	// in the end just close the </style> block
	echo '</style>
	<!-- Lithe custom product colors END -->
	';
}

// === Wholesale checks and actions
// Redirect wholesale users from products to wholesale form and non-wholesale users the other way around ... plus few more tricks - like login and (non)WS products
add_action('template_redirect', 'molswc_redirect_wholesalers');
function molswc_redirect_wholesalers () {
	$curr_slug = get_post_field( 'post_name', get_post() );
	$curr_url = rtrim(get_permalink(), "/");
	if ( molswc_is_wholesale_user() ) { 
		if ( is_shop() && !is_product() ) 						{ wp_redirect('/wholesale/order-form/'); 	exit(); }
		if ( is_product() && !molswc_is_wholesale_product() )	{ wp_redirect($curr_url.'-ws'); 			exit(); }
		if ( $curr_slug == 'login' ) 							{ wp_redirect('/wholesale/order-form/'); 	exit(); }
	}
	if ( !molswc_is_wholesale_user() ) {
		if ( $curr_slug == 'order-form' ) 						{ wp_redirect('/wholesale/login/'); 		exit(); }
		if ( is_product() && molswc_is_wholesale_product() )	{ wp_redirect(rtrim($curr_url, "-ws")); 	exit(); }
	}
}
// functions to check if wholesale or not, for both user and product
function molswc_is_wholesale_user() {
	$curr_user_roles = wp_get_current_user()->roles;
	if ( in_array('wholesale_customer', $curr_user_roles) ) { return true; }
}
function molswc_is_wholesale_product() {
	if ( has_term( 'wholesale', 'product_cat' ) ) { return true; }
}
// No wholesale products in Shop, even for admins
add_action( 'woocommerce_product_query', 'molswc_no_wholesale_products_in_shop' ); 
function molswc_no_wholesale_products_in_shop( $q ) {
    $tax_query = (array) $q->get( 'tax_query' );
    $tax_query[] = array(
           'taxonomy' => 'product_cat',
           'field' => 'slug',
           'terms' => array( 'wholesale' ), 
           'operator' => 'NOT IN'
    );
    $q->set( 'tax_query', $tax_query );
}

// === Woocommerce templates overrides
// Override WooCommerce Template used in WC Variations Radio Buttons
add_filter( 'woocommerce_locate_template', 'molswc_replace_woocommerce_templates', 20, 3 );
function molswc_replace_woocommerce_templates( $template, $template_name, $template_path ) {
	global $woocommerce;
	$_template = $template;
	if ( ! $template_path ) { $template_path = $woocommerce->template_url; }
	$plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/template/woocommerce/';
	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			$template_path . $template_name,
			$template_name
		)
	);
	if ( ! $template && file_exists( $plugin_path . $template_name ) ) { $template = $plugin_path . $template_name; }
	if ( ! $template ) { $template = $_template; }
	return $template;
}
// Override WooCommerce Template Parts, now the "content-product.php" file, where we'll add data_attrib to <li> element later...
add_filter( 'wc_get_template_part', 'molswc_override_woocommerce_template_part', 10, 3 );
function molswc_override_woocommerce_template_part( $template, $slug, $name ) {
    $template_directory = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/template/woocommerce/';
    if ( $name ) {
        $path = $template_directory . "{$slug}-{$name}.php";
    } else {
        $path = $template_directory . "{$slug}.php";
    }
    return file_exists( $path ) ? $path : $template;
}

// === Customize variation buttons displayed in both product page and product pop up
// Replace ATTRIBUTE TYPE variations buttons function of WC Variations Radio Buttons plugin, in order to add the *class needed to hook the variation price hints* JS
if ( ! function_exists( 'print_attribute_radio_attrib' ) ) {
	function print_attribute_radio_attrib( $checked_value, $value, $label, $name ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$parent_product_id = $product->get_id(); // getting the parent product ID
		$id = esc_attr( $name . '_v_' . $value . '_p_'. $parent_product_id ); // added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		$peer_vars = molswc_get_peer_variations($parent_product_id, $name, $value); // getting the peer variations (see the function for details)
		if ($peer_vars) { // Now, if there's any peer_vars found ...
			$peer_var_non_subscription_available = 'no'; // ... start assuming there's no non-subscription product available ...
			foreach ($peer_vars as $peer_var) { // ... then iterate through all peer_vars ...
				$peer_var_stock[$peer_var] = molswc_calculate_true_stock_status($peer_var)['true_stock_status']; // ... and get the stock for each peer variation ...
					if ( !Subscriptio_Subscription_Product::is_subscription($peer_var) ) { // ... then check current peer_var is NOT subscription ...
						$peer_var_non_subscription_available = 'yes'; // ... and if it's not, set the non_subscription_available flag to 'yes'
					} 
			}
			$subs_user = molswc_check_user_subscription_able(); // Now check if current user is subscription-able ...
			if ($subs_user == 'no' && $peer_var_non_subscription_available == 'no') { // ... then check if the user subscription-able status AND the non-subscription product available
				$lowest_peer_var_stock = 'n/a'; // user non-subscription-able and no non-subscription plan available? Then prepare it to be shown as unavailable!
			} else {
				$lowest_peer_var_stock = min($peer_var_stock); // Otherwise get the lowest stock from all variations, just in case (it should be synced anyway)
			}
		} else {
			$lowest_peer_var_stock = 'n/a'; // get something in case of no stock at all
		}
		// $lowest_peer_var_stock may be either: 1 = 'true_preorder' OR 2 = 'true_backorder'; OR 3 = 'true_instock';
		// Now define the class that will be applied:
		if ( is_numeric($lowest_peer_var_stock) ) {
			if ( $lowest_peer_var_stock == 1 ) {
				$stock_class = 'var_stock_preorder';
				$stock_hint = strip_tags(get_option( 'molswc_preorder_label' ));
			} elseif ( $lowest_peer_var_stock == 2 ) {
				$stock_class = 'var_stock_backorder';
				$stock_hint = strip_tags(get_option( 'molswc_backorder_label' ));
			} elseif ( $lowest_peer_var_stock == 3 ) {
				$stock_class = 'var_stock_instock';
				$stock_hint = strip_tags(get_option( 'molswc_instock_label' ));
			}
		} else {
			$stock_class = 'var_stock_not_available';
			$stock_hint = strip_tags(get_option( 'molswc_notavailable_label' ));
		}
		// Finally output the button html:
		printf( '<div class="attrib %6$s"><input type="radio" name="%1$s" value="%2$s" data-stock-status="%6$s" id="%3$s" %4$s /><label class="attrib option" value="%2$s" for="%3$s" data-text-fullname="%2$s" data-text-b="%5$s"><span class="inner-attrib">%5$s<span class="stock_hint %6$s">%7$s</span></span></label></div>', $input_name, $esc_value, $id, $checked, $filtered_label, $stock_class, $stock_hint );
	}
}
// Replace TAXONOMY TYPE variations buttons function of WC Variations Radio Buttons plugin, in order to add the *variation description*
if ( ! function_exists( 'print_attribute_radio_tax' ) ) {
	function print_attribute_radio_tax( $checked_value, $value, $label, $name, $attrib_description ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . '_p_'. $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="buying tax" data-text-name="%2$s">
					<label class="button_wrapper" for="%3$s" data-button-for="%2$s">
						<input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s />
						<span class="tax option attrib-description" value="%2$s" data-text-fullname="%5$s" data-text-b="%5$s">%5$s</span>
						<span class="attrib-description">%6$s</span>
					</label>
				</div>', $input_name, $esc_value, $id, $checked, $filtered_label, $attrib_description );
	}
}
// A function that generates the independent Add to Cart button for products
// That should be only one, so care should be taken upstream while defining it!
if ( ! function_exists( 'print_attribute_radio_tax_addtocart' ) ) {
	function print_attribute_radio_tax_addtocart( $checked_value, $value, $label, $name, $attrib_description ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . '_p_'. $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="buying tax addtocart" data-text-name="%2$s">
					<div class="button_wrapper addtocart" data-addtocart-for="%2$s">
						<input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s />
						<label class="tax option addtocart" value="%2$s" for="%3$s" data-text-fullname="%5$s" data-text-b="%5$s">Add to Cart</label>
					</div>
				</div>', $input_name, $esc_value, $id, $checked, $filtered_label, $attrib_description );
	}
}

// === Layout and output customization below:
// Various Woocommerce layout adjustments
// More layouts adjustments done also in Shortcodes displaying Decks Rack and Soft Goods - see the "add_shortcode" functions a bit further below this file
add_action('init', 'molswc_layout_adjustments');
function molswc_layout_adjustments() {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 ); // remove short description from original place ...
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_excerpt', 20 ); // ... and add it again at the end of product page (at woocommerce_after_single_product_summary )
	remove_action( 'xoo-qv-images','xoo_qv_product_image',20); // also remove the regular image from popup ...
	add_action( 'xoo-qv-images', array('SmartProductPlugin', 'wooCommerceImageAction'), 19 ); // ... and replace it with Product Smart Spinner:
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_rating', 10 ); // remove XOO Product Popup actions: rating
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_price', 15 ); // remove XOO Product Popup actions: price range
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_excerpt', 20 ); // remove XOO Product Popup actions: excerpt
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_meta', 30 ); // remove XOO Product Popup actions: meta
	remove_action( 'xoo-qv-summary', 'xoo_qv_product_info',35 ); // remove Learn More default button from XOO popup (will add a custom one later on)
	remove_action( 'woocommerce_after_single_product_summary','avia_woocommerce_output_related_products',20); // remove related products in single product page:
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'avia_woocommerce_frontend_search_params', 20 ); // remove ordering and products per page
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 ); // get rid of sale flash
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 ); // no more SKU and Cats on product page
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 ); // remove price range after title in single product *but not in popup*
	remove_action( 'woocommerce_product_tabs', 'woocommerce_default_product_tabs', 10 );
}
// And some advanced, conditional layout adjustments
add_action('wp', 'molswc_advanced_layout_adjustments');
function molswc_advanced_layout_adjustments() {
	if (is_product()) { // only in product page, otherwise breaks the boards list archive,
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 ); // remove the title from its original location ...
		add_action( 'woocommerce_before_main_content', 'woocommerce_template_single_title', 10 ); // ... and add it back on top of the page. *** NO HOOK ON POPUP THOUGH, SO WE CAN'T MOVE IT THERE ***
	}
}
// Prevent Left/Right product hints to show in product pages. Could change its settings and made it show, see it in parent theme, in "functions-enfold.php" file around line 505
if(!function_exists('avia_post_nav')) {
	function avia_post_nav($same_category = false, $taxonomy = 'category') { return; }
}
// Adding Mobile Scroll Hint Icon in Product Pop Up
add_action( 'xoo-qv-images', 'molswc_mobile_scroll_hint', 999 );
function molswc_mobile_scroll_hint () {
	if ( wp_is_mobile() ) {
		echo '
			<div class="scroll-hint center"><div class="mouse"><div class="wheel"></div></div>
			<div><span class="unu"></span><span class="doi"></span><span class="trei"></span></div></div>
		';
	}
}
// Increse number of products per SHop high enough to make sure no pagination occurs
add_filter( 'loop_shop_per_page', 'molswc_products_per_page', 20 );
function molswc_products_per_page( $cols ) {
  $cols = 100;
  return $cols;
}
// Replicating the WC product loop START, with the purpose to add a custom do_action, so we can hook custom stuff later on
if ( ! function_exists( 'woocommerce_product_loop_start' ) ) {
	function woocommerce_product_loop_start( $echo = true ) {
		ob_start();
		do_action( 'molswc_before_loop_start' );
		wc_set_loop_prop( 'loop', 0 );
		wc_get_template( 'loop/loop-start.php' );
		$loop_start = apply_filters( 'woocommerce_product_loop_start', ob_get_clean() );
		if ( $echo ) { echo $loop_start; } else { return $loop_start; }
	}
}
// Replicating the WC product loop END, with the purpose to add a custom do_action, so we can hook custom stuff later on
if ( ! function_exists( 'woocommerce_product_loop_end' ) ) {
	function woocommerce_product_loop_end( $echo = true ) {
		ob_start();
		wc_get_template( 'loop/loop-end.php' );
		do_action( 'molswc_after_loop_end' );
		$loop_end = apply_filters( 'woocommerce_product_loop_end', ob_get_clean() );
		if ( $echo ) { echo $loop_end; } else { return $loop_end; }
	}
}
// Rack bottom and Rack top images before and after "the product loop" 
// Adding Rack Top:
//add_action( 'molswc_before_loop_start', 'molswc_rack_top_image', 100 );
function molswc_rack_top_image() {
	$top_image_file_url = plugins_url('images/Rack_Top.png', __FILE__);
	echo '
	<div class="rack_top">
		<img class="rack_top_image" src="'.$top_image_file_url.'" alt="Lithe Skateboards Rack Top">
	</div>';
}
// Adding Rack Bottom:
//add_action( 'molswc_after_loop_end', 'molswc_rack_bottom_image', 100 );
function molswc_rack_bottom_image() {
	$bottom_image_file_url = plugins_url('images/Rack_Bottom.png', __FILE__);
	echo '
	<div class="rack_bottom">
		<img class="rack_bottom_image" src="'.$bottom_image_file_url.'" alt="Lithe Skateboards Rack Bottom">
	</div>';
}
// Wrap woocommerce loop with a div, for the purpose of adding the "rack-type" trigger class to it later
// Start the wrapping div
//add_action( 'molswc_before_loop_start', 'molswc_start_lithe_rack_type_display', 0 );
function molswc_start_lithe_rack_type_display() {
	echo '<div class="lithe_rack_type_display">';
}
// End the wrapping div
//add_action( 'molswc_after_loop_end', 'molswc_stop_lithe_rack_type_display', 999999 );
function molswc_stop_lithe_rack_type_display() {
	echo '</div>';
}

// === Creating shortcodes to add to cart the current product
// Without args it will add to cart 1 piece of current product
// Complete args example: [lithe_single_addtocart quantity="3" text="Buy 3!"] 
add_shortcode( 'lithe_single_addtocart', 'molswc_single_addtocart_shortcode' );
function molswc_single_addtocart_shortcode( $atts ) {
	global $product;
	if( is_product() && $product->is_type('simple') ) {
		$int_prod_id = $product->id;
		$int_quantity = wp_strip_all_tags($atts['quantity']);
		$int_text = wp_strip_all_tags($atts['text']);
		if(empty($int_quantity)) { $int_quantity = '1'; }
		if(empty($int_text)) { $int_text = 'Add to Cart'; }
		$lithe_button_complete = '
			<span class="single-addtocart-shortcode-wrapper">
				<span class="single-addtocart-shortcode">
					<a href="?add-to-cart='.$int_prod_id.'&quantity='.$int_quantity.'">'.$int_text.'</a>
				</span>
			</span>
		';	
	} else {
		if( current_user_can('edit_pages') ) {
			$lithe_button_complete = '<span style="color: red; font-size: 14px;"><strong>lithe_single_addtocart</strong> shortcode only works in single & simple products (only admins and editors can see this notice)</span>';
		}
	}
	return $lithe_button_complete;
}

// === Using a shortcode to fire up the board racks, using internally a Woocommerce shortcode and few other params we need for customizing list as rack
add_shortcode( 'lithe_rack', 'molswc_lithe_rack_generator' );
function molswc_lithe_rack_generator( $atts ) {
	// Since we're in Decks display let's remove some unneeded parts of the product list
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'avia_add_cart_button', 16 ); 
	
	// get attributes first. in a form that can be used to compose the woocommerce shortcode
	$int_category = $atts['category'];
	$int_columns = $atts['columns'];
	$int_noracks = $atts['no_racks']; // ... and have the ID passed by in shortcode (213596)
	$noproducts_image = wp_get_attachment_image($int_noracks, array(1000, 250));
	// <img width="1000" height="250" src="/wp-content/uploads/2020/02/SD2_NoWay_01.png" class="attachment-shop_catalog size-shop_catalog wp-post-image" alt="">
	
	// compose the shortcode as it would be put in content but based on atts above
	$shortcode_composer = '[products category="'.$int_category.'" class="real_products" columns="'.$int_columns.'"]';
	$woo_shortcode = do_shortcode($shortcode_composer);
	// now get the top and bottom images for rack
	$top_image_file_url = plugins_url('images/Rack_Top.png', __FILE__);
	$bottom_image_file_url = plugins_url('images/Rack_Bottom.png', __FILE__);
	// start writing the HTML OPENING of the racks
	
	$lithe_no_products = '
		<div class="woocommerce not_products" style="display: none;">
			<ul class="products">
				<li class="product first type-product status-publish has-post-thumbnail instock taxable purchasable product-type-variable">
					<div class="inner_product main_color wrapped_style noLightbox  av-product-class-">
						<a href="#" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
							<div class="thumbnail_container">
								'.$noproducts_image.'
							</div>
						</a>
					</div>
				</li>
			</ul>
		</div>
	';
	$lithe_rack_start = '
		<!-- Rack START: -->
		<div class="lithe_rack_type_display">
	';
	$lithe_rack_top = '
		<div class="rack_top">
			<img class="rack_top_image" src="'.$top_image_file_url.'" alt="Lithe Skateboards Rack Top">
		</div>
	';
	// $woo_shortcode will be inserted here, see below
	// writing the HTML CLOSING of the racks
	$lithe_rack_bottom = '
		<div class="rack_bottom">
			<img class="rack_bottom_image" src="'.$bottom_image_file_url.'" alt="Lithe Skateboards Rack Bottom">
		</div>
	';
	$lithe_rack_stop = '
		</div>
		<!-- Rack END. v07 -->
	';
	// now put everything in one string
	$lithe_rack_complete = $lithe_rack_start.$lithe_rack_top.$woo_shortcode.$lithe_rack_bottom.$lithe_no_products.$lithe_rack_stop;
	// ... and finally return it for usage wherever is needed :-)
	return $lithe_rack_complete;
}

// === Using a shortcode to fire up the soft goods list, using internally a Woocommerce shortcode and few other params we need for customizing that list 
add_shortcode( 'lithe_softgoods', 'molswc_lithe_softgoods_generator' );
function molswc_lithe_softgoods_generator( $atts ) {
	// get attributes first. in a form that can be used to compose the woocommerce shortcode
	$int_category = $atts['category'];
	$int_columns = $atts['columns'];
	$int_noproducts = $atts['noproducts']; // ... and have the ID passed by in shortcode (??)
	$noproducts_image = wp_get_attachment_image($int_noproducts, array(1000, 250));
	
	// compose the shortcode as it would be put in content but based on atts above
	$shortcode_composer = '[products category="'.$int_category.'" class="real_softgoods" columns="'.$int_columns.'"]';
	$woo_shortcode = do_shortcode($shortcode_composer);
	
	// transfer the shortcode content into a new variable, making it ready to add more HTML before its start and after its end
	$lithe_softgoods_complete = $woo_shortcode;
	
	// ... and finally return it for usage wherever is needed :-)
	return $lithe_softgoods_complete;
}

// === Adding a new custom button after variations buttons in Xoo Popup
// Display the button in the popup
add_action( 'xoo-qv-summary', 'molswc_custom_learnmore_button_for_xoopopup', 35 );
function molswc_custom_learnmore_button_for_xoopopup(){
	// get global and default data
	global $product;
	$product_id = $product->get_id();
	// Get button text
	$molswc_product_learnmore_button_text = get_post_meta ($product_id, 'molswc_product_learnmore_button_text')[0];
	// Set a default button text in case none is defined
	if( !$molswc_product_learnmore_button_text ) { $molswc_product_learnmore_button_text = 'Learn more'; }
	// Get link type
	$molswc_product_learnmore_link_type   = get_post_meta ($product_id, 'molswc_product_learnmore_link_type')[0];
	// Set it to "normal" in case it is not defined
	if( !$molswc_product_learnmore_link_type ) { $molswc_product_learnmore_link_type = 'normal'; }
	// Set the custom link based on link type
	if( $molswc_product_learnmore_link_type == 'custom' ) { 
		$molswc_product_learnmore_button_link = get_post_meta ($product_id, 'molswc_product_learnmore_button_link')[0];
	} else {
		$molswc_product_learnmore_button_link = get_permalink();
	}
	// Set a default link in case still none is defined (for backward compatibility reasons)
	if( !$molswc_product_learnmore_button_link ) { $molswc_product_learnmore_button_link = get_permalink(); }
	$html  = '<div class="xoo-qv-plink">';
	$html .=  '<a href="'.$molswc_product_learnmore_button_link.'">'.$molswc_product_learnmore_button_text.'</a>';
	$html .= '</div>';
	echo $html;
}
// Register a product meta-box for customizing the Learn More button in Xoo Popup
add_action( 'add_meta_boxes', 'molswc_customize_learnmore_settings' );
function molswc_customize_learnmore_settings() {
    add_meta_box(
        'molswc_customize_learnmore',			// this is HTML id of the box on edit screen
        'Customize Learn More button in popup',	// title of the box
        'molswc_customize_learnmore_content',	// function to be called to display the checkboxes, see the function below
        'product',		// on which edit screen the box should appear
        'normal',		// part of page where the box should appear
        'default'		// priority of the box
    );
}
// Output the meta-box registered above in product edit screen
function molswc_customize_learnmore_content() {
	wp_nonce_field( plugin_basename( __FILE__ ), 'customlearnmorenonce' );
	global $post;
	$curr_prod_id = $post->ID;
	// pull the link tupe option from DB first
	if( get_post_meta( $curr_prod_id, "molswc_product_learnmore_link_type" )[0] == 'normal' ) { $molswc_product_learnmore_link_type_normal = 'checked="checked"'; }
	if( get_post_meta( $curr_prod_id, "molswc_product_learnmore_link_type" )[0] == 'custom' ) { $molswc_product_learnmore_link_type_custom = 'checked="checked"'; }
	// if no option set for link type then assume the "normal" one
	if( !$molswc_product_learnmore_link_type_normal && !$molswc_product_learnmore_link_type_custom ) { $molswc_product_learnmore_link_type_normal = 'checked="checked"'; }
	// ... then echo the options form in product edit screen
	echo '
	<p>Set the button text and link below:</p>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Button text: </th>
			<td colspan="4">
				<input name="molswc_product_learnmore_button_text" type="text" id="molswc_product_learnmore_button_text" style="display: inline-block; width: auto;" aria-describedby="molswc_product_learnmore_button_text" value="'.get_post_meta( $curr_prod_id, "molswc_product_learnmore_button_text" )[0].'" class="regular-text">
				<span> (displays "Learn more" if empty)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Button link type: </th>
			<td colspan="4"> 
				<fieldset style="display:inline-block;"> 
					<label><input name="molswc_product_learnmore_link_type" type="radio" value="normal"'.$molswc_product_learnmore_link_type_normal.'>Normal product link</label><br>
					<label><input name="molswc_product_learnmore_link_type" type="radio" value="custom"'.$molswc_product_learnmore_link_type_custom.'>Custom link ↴ </label>
				</fieldset>
				
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Custom link: </th>
			<td colspan="4">
				<input name="molswc_product_learnmore_button_link" type="text" id="molswc_product_learnmore_button_link" style="display: inline-block; width: auto;" aria-describedby="molswc_product_learnmore_button_link" value="'.get_post_meta( $curr_prod_id, "molswc_product_learnmore_button_link" )[0].'" class="regular-text">
				<span> (if this field is empty and type set to "Custom link" above, it will still link to normal product page)</span>
			</td>
		</tr>
	</table>
	';
}
// Save metabox data at product update
add_action( 'woocommerce_update_product', 'molswc_customize_learnmore_data' );
function molswc_customize_learnmore_data($curr_prod_id) {
    // check some conditions before saving the fields
	if ( !current_user_can('manage_woocommerce') ) { return; }
    if ( !wp_verify_nonce( $_POST['customlearnmorenonce'], plugin_basename( __FILE__ ) ) ) { return; }
    // ... then store colors data in custom post meta based on field values 
    if ( isset( $_POST['molswc_product_learnmore_button_text'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_product_learnmore_button_text', strip_tags($_POST['molswc_product_learnmore_button_text']) ); }
	if ( isset( $_POST['molswc_product_learnmore_link_type'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_product_learnmore_link_type', strip_tags($_POST['molswc_product_learnmore_link_type']) ); }
	if ( isset( $_POST['molswc_product_learnmore_button_link'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_product_learnmore_button_link', strip_tags($_POST['molswc_product_learnmore_button_link']) ); }
}

// === Adding the Add to Cart display type option
// Register a product meta-box for choosing an Add to Cart display type
add_action( 'add_meta_boxes', 'molswc_choose_single_product_display_type' );
function molswc_choose_single_product_display_type() {
    add_meta_box(
        'molswc_choose_single_product_display_type',			// this is HTML id of the box on edit screen
        'Customize Add to Cart display type in Single Product page',	// title of the box
        'molswc_choose_single_product_display_type_content',	// function to be called to display the checkboxes, see the function below
        'product',		// on which edit screen the box should appear
        'normal',		// part of page where the box should appear
        'default'		// priority of the box
    );
}
// Output the meta-box registered above in product edit screen
function molswc_choose_single_product_display_type_content() {
	wp_nonce_field( plugin_basename( __FILE__ ), 'choosesingleproductdisplaytypenonce' );
	global $post;
	$curr_prod_id = $post->ID;
	// pull the link tupe option from DB first
	if( get_post_meta( $curr_prod_id, "molswc_choose_single_product_display_type" )[0] == 'board' ) { $molswc_choose_single_product_display_type_board = 'checked="checked"'; }
	if( get_post_meta( $curr_prod_id, "molswc_choose_single_product_display_type" )[0] == 'soft'  ) { $molswc_choose_single_product_display_type_soft =  'checked="checked"'; }
	// if no option set for link type then assume the "normal" one
	if( !$molswc_choose_single_product_display_type_board && !$molswc_choose_single_product_display_type_soft ) { $molswc_choose_single_product_display_type_board = 'checked="checked"'; }
	// ... then echo the options form in product edit screen
	echo '
	<p>Set the Add to Cart type to display for this product:</p>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Add to Cart display type: </th>
			<td colspan="4"> 
				<fieldset style="display:inline-block;"> 
					<label><input name="molswc_choose_single_product_display_type" type="radio" value="board"'.$molswc_choose_single_product_display_type_board.'>Board type display (with buttons)</label><br>
					<label><input name="molswc_choose_single_product_display_type" type="radio" value="soft"'.$molswc_choose_single_product_display_type_soft.'>Accessories type display (with drop-down)</label>
				</fieldset>
			</td>
		</tr>
	</table>
	';
}
// Save metabox data at product update
add_action( 'woocommerce_update_product', 'molswc_choose_single_product_display_type_data' );
function molswc_choose_single_product_display_type_data($curr_prod_id) {
    // check some conditions before saving the fields
	if ( !current_user_can('manage_woocommerce') ) { return; }
    if ( !wp_verify_nonce( $_POST['choosesingleproductdisplaytypenonce'], plugin_basename( __FILE__ ) ) ) { return; }
    // ... then store the data in custom post meta based on field values 
	if ( isset( $_POST['molswc_choose_single_product_display_type'] ) ) {  update_post_meta( $curr_prod_id, 'molswc_choose_single_product_display_type', strip_tags($_POST['molswc_choose_single_product_display_type']) ); }
}

// === Outputting JS variables in HTML, for using them later in JS file
add_action( 'wp_head', 'molswc_js_variables' ); 
function molswc_js_variables() {
echo "
<script type='text/javascript'>
/* <![CDATA[ */";
														  echo "\r\n var subs_user = '".molswc_check_user_subscription_able()."';"; 
	if (get_option( 'molswc_estdelivery_instock' )) 	{ echo "\r\n var estdelivery_instock = '".strip_tags(get_option( 'molswc_estdelivery_instock' ))."';"; }
	if (get_option( 'molswc_estdelivery_backorder' )) 	{ echo "\r\n var estdelivery_backorder = '".strip_tags(get_option( 'molswc_estdelivery_backorder' ))."';"; }
	if (get_option( 'molswc_estdelivery_preorder' )) 	{ echo "\r\n var estdelivery_preorder = '".strip_tags(get_option( 'molswc_estdelivery_preorder' ))."';"; }
	if (get_option( 'molswc_pre_order_message' )) 		{ echo "\r\n var pre_order_message = '".strip_tags(get_option( 'molswc_pre_order_message' ))."';"; }
echo "
/* ]]> */
</script>
";
}

// === The product filters
// Function to pull the the variations with required stock status based on attributes, for adding them to product LI element. 
// Used in "content-product.php" file in this plugin
// Product Filters JS works based on data added by this function to those LI elements
function molswc_instock_variations() {
	$data_custom_attribs_list = array();
	$data_custom_attribs_list_all = array();
	global $product; 
	$variations1=$product->get_children();
	foreach ($variations1 as $value) { 
		$var_true_stock_status = molswc_calculate_true_stock_status($value)['true_stock_status'];
		$required_true_stock_status = strip_tags(get_option( 'molswc_true_stock_level_admitted_in_filters' ));
		$single_variation=new WC_Product_Variation($value);
		$var_is_purc = $single_variation->is_purchasable();
		if ( $var_is_purc == 1 && $var_true_stock_status >= $required_true_stock_status) { 
			$var_model_and_size = array_values($single_variation->get_variation_attributes())[0];
			$data_custom_attribs_list_all[] = $var_model_and_size; 
		}
	}
	if($data_custom_attribs_list_all) { $data_custom_attribs_list = array_unique($data_custom_attribs_list_all); }
	return $data_custom_attribs_list;
}
// Creating a board filter drop down list that we'll display with a shortcode
// Will be populated in browser, via JS functions, with the available Models and Widths picked up from LI elements of the products
add_shortcode( 'board_filters', 'molswc_insert_filters_shortcode' );
function molswc_insert_filters_shortcode(){
	// pick possible model and width from URL
	// $preselect_model = strtolower(strip_tags($_GET["model"]));
	// $preselect_width = strip_tags($_GET["width"]);
	$selectors_html = '
		<form id="product-filters" class="product-filters">
			<select id="filterModels" class="product-filters-dropdowns" name="Models">
			  <option value="" selected disabled hidden>All Shapes</option>
			</select>
			<select id="filterWidths" class="product-filters-dropdowns" name="Widths">
			  <option value="" selected disabled hidden>All Widths</option>
			</select>
			<a id="reset-product-filters" class="product-filters-reset" href="#/">X</a>
		</form>
	';
	return $selectors_html;
}

// === User subscription-able
// Adding the option in the user admin screen:
add_action( 'show_user_profile', 'molswc_user_subscription_able' );
add_action( 'edit_user_profile', 'molswc_user_subscription_able' );
function molswc_user_subscription_able( $user ) { 
    if ( !current_user_can( 'manage_woocommerce', $user ) ) { 
		return false; 
	} else {
		$user_subscription_able = get_the_author_meta( 'user_subscription_able', $user->ID );
		if ($user_subscription_able == yes) {$user_subscription_able_checked = 'checked="checked"';} else { $user_subscription_able_checked = ''; }
		echo '<h3>User subscription-able</h3>
		<table class="form-table">
			<tr>
				<th><label for="user_subscription_able">Can this user purchase subscriptions?</label></th>
				<td>';
					echo '
					<label for="user_subscription_able">
						<input name="user_subscription_able" type="checkbox" id="user_subscription_able" value="yes" '.$user_subscription_able_checked.'>';
						_e("Check to allow subscriptions for this user."); echo '
					</label>
					'; echo '
				</td>
			</tr>
		</table>';
	}
}
// Edit the option in the user admin screen:
add_action( 'personal_options_update', 'molswc_user_subscription_able_edit' );
add_action( 'edit_user_profile_update', 'molswc_user_subscription_able_edit' );
function molswc_user_subscription_able_edit( $user_id ) {
    if ( !current_user_can( 'manage_woocommerce', $user_id ) ) { 
		return false; 
	} else {
		update_user_meta( $user_id, 'user_subscription_able', $_POST['user_subscription_able'] );
    }
}
// Check SUBSCRIPTION-ABLE user condition (used in the function below)
function molswc_check_user_subscription_able() {
	$curr_user_id = get_current_user_id();
	if ( $curr_user_id != 0 ) {
		$um_value = get_user_meta( $curr_user_id, 'user_subscription_able', true );
		if ( ! empty( $um_value ) && $um_value == 'yes' ) {
			$subscription_user = 'yes'; 
		} else {
			$subscription_user = 'no'; 
		}
	} else {
		$subscription_user = 'no'; 
	}
	return $subscription_user;
}
// Deactivate subscription type variations based on user SUBSCRIPTION-ABLE condition
add_filter( 'woocommerce_variation_is_active', 'molswc_disable_variations_user_based', 10, 2 );
function molswc_disable_variations_user_based( $active, $variation ) {
	$subs_user = molswc_check_user_subscription_able();
	$variation_id = $variation->variation_id;
	if ( Subscriptio_Subscription_Product::is_subscription($variation_id) && $subs_user == 'no' ) {
		return false;
    } else {
		return true;
    }
}

// === Peer Variation stock syncing functions below!
// Sync peer variations stock at placing new order. (peer variations are those which have the same 'model-and-size' attribute but different 'payment-plan' global attribute)
add_action( 'woocommerce_reduce_order_stock', array('Wc_class', 'molswc_stock_adjutments'));
class Wc_class {
	public static function molswc_stock_adjutments($order) {
		$peer_vars_working_array = array();
		foreach ( $order->get_items() as $item_id => $item_values ) { // Iterating though each order items
			$item_qty = $item_values['qty']; // Item quantity
			$current_var_id = $item_values['variation_id']; // current variation ID 
			if ( $current_var_id ) {
				$current_var = wc_get_product( $current_var_id ); // Get an instance of the current variation object
				$current_var_stock = $current_var->get_stock_quantity(); // Get the stock quantity of the current_var
				$prepared_data = molswc_get_peer_variations_prepare( $current_var_id );
				$peer_vars = molswc_get_peer_variations($prepared_data['parent_id'], $prepared_data['attrib_to_use_for_peering'], $prepared_data['value_to_use_for_peering']); // Just get the peer variations
				foreach ( $peer_vars as $peer_var ) {
				// Start creating the $peer_vars_working_array to store the IDs of variations along with the appropriate stock level
					if ( !isset($peer_vars_working_array[$peer_var]) ) { // if this peer variations is not in working array ...
						$peer_vars_working_array[$peer_var] = $current_var_stock; // ...add it with ID as index and current stock as value
					} else { // otherwise ...
						$new_peer_var_stock_value = $peer_vars_working_array[$peer_var] - $item_qty; // ...subtract current variation quantity from the value stored in the working array ...
						$peer_vars_working_array[$peer_var] = $new_peer_var_stock_value; // ...and store the new value in the working array
					}
				}
			}
		}
		// Start iterating through the working array containing to-be-synced variations, stored with ID as key and new stock as value
		foreach ( $peer_vars_working_array as $peer_var_id => $peer_var_stock ) {
			wc_update_product_stock( $peer_var_id, $peer_var_stock, 'set' ); // finally update stock level of peer variation
		}
	}
}
// Function to check if a custom attribute is in the "desired" attributes, based on which stock sync will be done ... but maybe other operations too
function molswc_check_if_custom_attrib_exists($attribs_array) {
	$possible_custom_attribs = array('model-and-size', 'model-width', 'shape-width'); // define these in Admin Page if anything. Shouldn't doing it tho ... Or even better, read all custom attributes from all products (limit to Decks category?)
	foreach ($possible_custom_attribs as $possible_custom_attrib) {
		if (array_key_exists($possible_custom_attrib, $attribs_array)) {
			return $possible_custom_attrib;
		}
	}
}
// Prepare data for "get_peer_variations". Takes a Variation ID and returns an array with its Parent ID, Attribute For Peering and Value for Peering
function molswc_get_peer_variations_prepare( $variation_id ) {
	$current_var = wc_get_product( $variation_id ); // Get an instance of the current variation object
	$parent_id = $current_var->get_parent_id(); // Get the ID of the parent product
	$current_var_attribs = wc_get_formatted_variation( $current_var->get_variation_attributes(), true ); // Get the attributes of the current variation
	parse_str(strtr($current_var_attribs, ":,", "=&"), $attribs_array); // parse the attributes object ...
	$attrib_to_use_for_peering = molswc_check_if_custom_attrib_exists($attribs_array); // now check which attribute we may use for peering ...
	$attrib_values = $current_var -> attributes; // ... then get all values of the attribute used for peering ...
	$value_to_use_for_peering = $attrib_values[$attrib_to_use_for_peering]; // ... and get the exact value we need for peeting.
	// Now build the array that we'll later return
	$returned_data = array (
		"parent_id" => 					$parent_id,
		"attrib_to_use_for_peering" => 	$attrib_to_use_for_peering,
		"value_to_use_for_peering" => 	$value_to_use_for_peering
	);
	return $returned_data; // and finally return the data!
}
// Get peer variations: loop through all children of a parent product by ID and return an array with all variations having same "...attr_value" into "...attr_name".
// See and use "molswc_get_peer_variations_prepare" function to get the parameters based on a single Variation ID
function molswc_get_peer_variations($parent_id, $based_on_attr_name, $based_on_attr_value) {
	$parent_product = wc_get_product( $parent_id ); // Get an instance of parent product
	$peer_variations = $parent_product->get_available_variations(); // Now get all the *possible* peer variations, those that are children of the same product
	foreach ($peer_variations as $peer_variation) {
		$peer_variation_id      = $peer_variation['variation_id']; // this is peer variation ID
		$peer_variation_product = wc_get_product( $peer_variation_id ); // this is peer variation product instance
		$peer_variation_attribs = wc_get_formatted_variation( $peer_variation_product->get_variation_attributes(), true ); // these are peer variation attributes
		parse_str(strtr($peer_variation_attribs, ":,", "=&"), $peer_var_attribs_array); // this is peer variation attributes *array*
		$peer_var_attrib_name = $peer_var_attribs_array[$based_on_attr_name]; // this is peer variation attribute name, based on this we'll judge *peers*
		if ( trim($peer_var_attrib_name) == trim($based_on_attr_value) ) { // check if this *IS* a peer variation (has same "...attr_name" into "...attr_value")
			$current_peer_vars_array[] = $peer_variation_id; // ...add it to $current_peer_vars_array
		}
	}
	return $current_peer_vars_array;
}

// === True Stock Data functions below! (uses some functions from above for syncing)
// Add back order stock level number box to each variation in its edit screen
add_action( 'woocommerce_variation_options_inventory', 'molswc_variation_backorder_stock_level', 10, 3 ); 
function molswc_variation_backorder_stock_level( $loop, $variation_data, $variation ) {
	woocommerce_wp_text_input( 
		array( 
			'id'          => 'backorder_stock_level[' . $variation->ID . ']', 
			'value'       => get_post_meta( $variation->ID, 'backorder_stock_level', true ), // Use these line to get it wherever it might be needed
			'type'        => 'number',
			'style' 	  => 'width: 100%; vertical-align: middle; margin: 2px 0 0; padding: 5px;',
			'label'       => __( 'Backorder stock level', 'woocommerce' ), 
			'desc_tip'    => 'true',
			'description' => __( 'How many back ordered boards are coming?', 'woocommerce' ),
			'custom_attributes' => array(
				'step' 	=> '1',
				'min'	=> '0'
			) 
		)
	);      
}
// Add True Stock Hint box in Variation Edit screen. No saving for it
add_action( 'woocommerce_variation_options_inventory', 'molswc_true_stock_data_hint', 10, 3 ); 
function molswc_true_stock_data_hint( $loop, $variation_data, $variation ) {
	$variation_id = $variation->ID;
	$truelevel = abs(molswc_calculate_true_stock_level($variation_id)['true_stock_level']);
	$truestatus = molswc_calculate_true_stock_status($variation_id)['true_stock_status'];
	if ($truestatus == 1) {$status_code = 'Po';}
	if ($truestatus == 2) {$status_code = 'Bo';}
	if ($truestatus == 3) {$status_code = 'St';}
	woocommerce_wp_text_input( 
		array( 
			'id' => 'true_stock_hint_['. $loop .']', 
			'label' => __( 'True Stock Hint', 'woocommerce' ), 
			'value' => $status_code.$truelevel,
			'style' 	  => 'width: 100%; vertical-align: middle; margin: 2px 0 0; padding: 5px;'
		) 
	);
}
// Store variation ID and other data in some Wp_Options, we'll use these to sync between variations at Variation Save, see below
// Also save the "backorder_stock_level" at this moment
add_action( 'woocommerce_save_product_variation', 'molswc_save_variation_backorder_stock_level', 10, 2 );
function molswc_save_variation_backorder_stock_level( $post_id ) {
	$parent_product_id = molswc_get_peer_variations_prepare($post_id)['parent_id']; // We need the parent product ID first
	// Then let's start taking care of the "backorder_stock_level" sync first
	$backorder_stock_level = $_POST['backorder_stock_level'][ $post_id ]; // Get the backorder_stock_level from Edit Form
	if(is_numeric( $backorder_stock_level ) ) {
		// First save the "backorder_stock_level" at this moment - this is mandatory for saving the "backorder_stock_level"
		update_post_meta( $post_id, 'backorder_stock_level', esc_attr( $backorder_stock_level ) );   
		// Then insert the variation ID and "backorder_stock_level" in an array stored in a Wp_Option for later sync
		$var_bo_stock_save_option_key = 'molswc_cached_fragment'.'_option_save_bo_stock_'.$parent_product_id; // Get key of option holding the currently saved variations
		$currently_saved_variations_bo_stock = get_option($var_bo_stock_save_option_key); // Get the very option holding the currently saved variations
		$currently_saved_variations_bo_stock[$post_id] = $backorder_stock_level; // Add the currently saved variation data
		update_option($var_bo_stock_save_option_key, $currently_saved_variations_bo_stock); // Insert it back in the Wp_Option
	}
	// Finally do the same for stock wp_option sync:
	$post_saved_index = array_search($post_id, $_POST['variable_post_id']); // Get the variation ID from the index of $_POST saved data 
	$main_stock_level = $_POST['variable_stock'][ $post_saved_index ]; // Then get the saved stock value for it
	if(is_numeric( $main_stock_level ) ) {
		$var_main_stock_save_option_key = 'molswc_cached_fragment'.'_option_save_main_stock_'.$parent_product_id; // Get key of option holding the currently saved variations
		$currently_saved_variations_main_stock = get_option($var_main_stock_save_option_key);
		$currently_saved_variations_main_stock[$post_id] = $main_stock_level;
		update_option($var_main_stock_save_option_key, $currently_saved_variations_main_stock);
	}
}
// Sync the "backorder_stock_level" of variations saved in Product Edit screen AND also set the "preorder_status" accordingly
// This only runs once at the end of saving the product variations, so we have all changed variations and their "backorder_stock_level" at this moment, see above
add_action( 'woocommerce_ajax_save_product_variations', 'molswc_bo_sync_on_product_save', 10, 1 );
function molswc_bo_sync_on_product_save( $product_id ) {
	$var_bo_stock_save_option_key = 'molswc_cached_fragment'.'_option_save_bo_stock_'.$product_id; // Get key of option holding the currently saved variations
	$currently_saved_variations_bo_stock = get_option($var_bo_stock_save_option_key); // Get the very option holding the currently saved variations
	foreach($currently_saved_variations_bo_stock as $cs_var_id => $cs_var_bo_stock) { // Now, for each variation that was changed and so we save it
		$prepared_data = molswc_get_peer_variations_prepare($cs_var_id); // prepare get_peer data ... 
		$peer_vars = molswc_get_peer_variations($prepared_data['parent_id'], $prepared_data['attrib_to_use_for_peering'], $prepared_data['value_to_use_for_peering']);  //... and fetch its peer variations
		foreach($peer_vars as $peer_var) { // Now, for each peer variation set a bo_stock value in an array from which we could pull the MINIMUM, the MAXIMUM or some other unique value later on
			if( !isset($currently_saved_variations_bo_stock[$peer_var]) ) {
				$peer_var_bo_stock[$peer_var] = $cs_var_bo_stock;
			} else {
				$peer_var_bo_stock[$peer_var] = $currently_saved_variations_bo_stock[$peer_var];
			}
		}
		foreach($peer_vars as $peer_var) { // Ok, now that we have all the bo_stock values available let's iterate again through all peer_vars
			update_post_meta( $peer_var, 'backorder_stock_level', min($peer_var_bo_stock) ); // First update the "backorder_stock_level", with the MINIMUM value,
			$peer_var_true_stock_status = molswc_calculate_true_stock_status($peer_var)['true_stock_status']; // Then calculate its true status ...
			if( $peer_var_true_stock_status == 1 ) { // ... and set it accordingly for the new "backorder_stock_level" that was set above
				molswc_set_preorder_status($peer_var, 'yes');
			} else {
				molswc_set_preorder_status($peer_var, 'no');
			}
		}
		unset($peer_var_bo_stock); // Now reset the bo_stock array so we can start evaluating the next set of peer_vars
		molswc_delete_fragments('molswc_cached_fragment_prod_form_'.$prepared_data['parent_id']); // Also delete the cached fragment for parent product of currently_saved_variation
	}
	$empty = array(); update_option($var_bo_stock_save_option_key, $empty); // In the end just empty that option, making it ready to be used next time
}
// Sync the Stock Level of variations saved in Product Edit screen
add_action( 'woocommerce_ajax_save_product_variations', 'molswc_main_stock_sync_on_product_save', 10, 1 );
function molswc_main_stock_sync_on_product_save( $product_id ) {
	$var_main_stock_save_option_key = 'molswc_cached_fragment'.'_option_save_main_stock_'.$product_id; // Get key of option holding the currently saved variations
	$currently_saved_variations_main_stock = get_option($var_main_stock_save_option_key); // Get the very option holding the currently saved variations
	foreach($currently_saved_variations_main_stock as $cs_var_id => $cs_var_main_stock) { // Now, for each variation that was changed and so we save it
		$prepared_data = molswc_get_peer_variations_prepare($cs_var_id); // prepare get_peer data ... 
		$peer_vars = molswc_get_peer_variations($prepared_data['parent_id'], $prepared_data['attrib_to_use_for_peering'], $prepared_data['value_to_use_for_peering']);  //... and fetch its peer variations
		foreach($peer_vars as $peer_var) { // Now, for each peer variation set a main_stock value in an array from which we could pull the MINIMUM, the MAXIMUM or some other unique value later on
			if( !isset($currently_saved_variations_main_stock[$peer_var]) ) {
				$peer_var_main_stock[$peer_var] = $cs_var_main_stock;
			} else {
				$peer_var_main_stock[$peer_var] = $currently_saved_variations_main_stock[$peer_var];
			}
		}
		foreach($peer_vars as $peer_var) { // Ok, now that we have all the main_stock values available let's iterate again through all peer_vars
			$minimum_peer_var_main_stock = min($peer_var_main_stock);
			wc_update_product_stock( $peer_var, $minimum_peer_var_main_stock, 'set' ); // finally update stock level of peer variation
			$peer_var_true_stock_status = molswc_calculate_true_stock_status($peer_var)['true_stock_status']; // Then calculate its true status ...
			if( $peer_var_true_stock_status == 1 ) { // ... and set it accordingly for the new "main_stock_level" that was set above
				molswc_set_preorder_status($peer_var, 'yes');
			} else {
				molswc_set_preorder_status($peer_var, 'no');
			}
		}
		unset($peer_var_main_stock); // Now reset the bo_stock array so we can start evaluating the next set of peer_vars
		molswc_delete_fragments('molswc_cached_fragment_prod_form_'.$prepared_data['parent_id']); // Also delete the cached fragment for parent product of currently_saved_variation
	}
	$empty = array(); update_option($var_main_stock_save_option_key, $empty); // In the end just empty that option, making it ready to be used next time
}
// Store back order stock level in variation meta data, so it gets outputted in the variations_form
add_filter( 'woocommerce_available_variation', 'molswc_store_variation_backorder_stock_level' );
function molswc_store_variation_backorder_stock_level( $variations ) {
    $variations['backorder_stock_level'] = get_post_meta( $variations[ 'variation_id' ], 'backorder_stock_level', true );
    return $variations;
}
// Calculate true stock LEVEL by variation ID
// Returns an array, check the items below to pick them
// Use: $truelevel = molswc_calculate_true_stock_level($variation_id)['true_stock_level'];
function molswc_calculate_true_stock_level($variation_id) {
	if($variation_id) {
		$variation_instance = wc_get_product( $variation_id ); // Get an instance of the current variation object
		$true_stock_data['woo_stock_level'] = $variation_instance->get_stock_quantity(); // Get the stock quantity of the current_var
		if(!is_numeric($true_stock_data['woo_stock_level'])) { $true_stock_data['woo_stock_level'] = 0; } // ... but set it to zero if it doesn't come out
		$true_stock_data['backorder_stock_level'] = get_post_meta( $variation_id, 'backorder_stock_level', true ); // Get the backorder_stock_level of the current_var
		if(!is_numeric($true_stock_data['backorder_stock_level'])) { $true_stock_data['backorder_stock_level'] = 0; } // ... but set it to zero if it doesn't come out
		$true_stock_data['true_stock_level'] = $true_stock_data['woo_stock_level'] + $true_stock_data['backorder_stock_level']; // Calculate true stock level
		return $true_stock_data;
	}
}
// Calculate true stock STATUS by variation ID
// Returns an array, check the items below to pick them
// Use: $truestatus = molswc_calculate_true_stock_status($variation_id)['true_stock_status'];
// Get: 1 = 'true_preorder' OR 2 = 'true_backorder'; OR 3 = 'true_instock';
function molswc_calculate_true_stock_status($variation_id) {
	$true_stock_data = molswc_calculate_true_stock_level($variation_id);
	if( $true_stock_data['woo_stock_level'] > 0 ) { // If woocommerce stock level is positive ...
		$true_stock_data['true_stock_status'] = 3; // 'true_instock'; // ...then report 'true_in_stock'
	} elseif ( $true_stock_data['woo_stock_level'] <= 0 && $true_stock_data['woo_stock_level'] > (0 - $true_stock_data['backorder_stock_level']) ) { // if woocommerce stock level is negative but above backorder
		$true_stock_data['true_stock_status'] = 2; // 'true_backorder'; // ... report 'true_backorder'
	} else { // otherwise ...
		$true_stock_data['true_stock_status'] = 1; // 'true_preorder'; // ... just report 'true_preorder'.
	}
	return $true_stock_data;
}
// Set preorder status of variation, aka "_ywpo_preorder" product meta, to 'yes' or 'no'
function molswc_set_preorder_status($variation_id, $is_preorder) {
	if( $is_preorder == 'yes' || $is_preorder == 'no' ) {
		update_post_meta( $variation_id, '_ywpo_preorder', $is_preorder );
	}
}
// Set *and sync!* the right pre order status of variation at purchase
add_action( 'woocommerce_reduce_order_stock', array('Wc_class_preorder_adjustments', 'molswc_adjust_preorder_status'));
class Wc_class_preorder_adjustments {
	public static function molswc_adjust_preorder_status($order) {
		foreach ( $order->get_items() as $item_id => $item_values ) { // Iterating though each order items
			if ( $item_values['variation_id'] ) {
				$prepared_data = molswc_get_peer_variations_prepare($item_values['variation_id']);
				$peer_vars = molswc_get_peer_variations($prepared_data['parent_id'], $prepared_data['attrib_to_use_for_peering'], $prepared_data['value_to_use_for_peering']); // Just get the peer variations
				// Simplest plug in function below. Just add each variation ID to an array that we'll process later, as soon as this foreach loop finishes 
				foreach($peer_vars as $peer_var) {
					$peer_vars_working[] = $peer_var;
				}
			}
		}
		// Main plug in function below - could do anything to process data fetched above
		// Now just process the $peer_vars_working array, applying the new "is_preorder" condition based on "true_stock_status"
		foreach ( $peer_vars_working as $working_var ) {
			$current_var_id_true_stock_status = molswc_calculate_true_stock_status($working_var)['true_stock_status']; 
			if( $current_var_id_true_stock_status == 1 ) {
				molswc_set_preorder_status($working_var, 'yes');
			} else {
				molswc_set_preorder_status($working_var, 'no');
			}
		}
	}
}

// === Make "True Stock Status" available in customer emails, cart, checkout and admin backend
// Save true_stock_status value in cart item for now 
add_filter( 'woocommerce_add_cart_item_data', 'molswc_save_true_stock_status_in_cart_object', 30, 3 );
function molswc_save_true_stock_status_in_cart_object( $cart_item_data, $product_id, $variation_id ) {
    // Get the correct Id first
    $the_id = $variation_id > 0 ? $variation_id : $product_id;
	// ... then get the True Status
    $truestatus_numerical = molswc_calculate_true_stock_status($the_id)['true_stock_status'];
	// ... then get the label set in the options, to stay consistent with front end 
	if ($truestatus_numerical == 3) { $truestatus = get_option('molswc_instock_label'); }
	if ($truestatus_numerical == 2) { $truestatus = get_option('molswc_backorder_label'); }
	if ($truestatus_numerical == 1) { $truestatus = get_option('molswc_preorder_label'); }
	// ... then add it to Cart Data
	$cart_item_data['true_stock_status'] = sanitize_text_field( $truestatus );
	// ... and finally return the Cart Data  
    return $cart_item_data;
}
// Display true_stock_status on cart and checkout pages
add_filter( 'woocommerce_get_item_data', 'molswc_display_true_stock_status_as_item_data', 20, 2 );
function molswc_display_true_stock_status_as_item_data( $cart_data, $cart_item ) {
    if( isset( $cart_item['true_stock_status'] ) ){
        $cart_data[] = array(
            'name' => __( 'Stock Status', 'woocommerce' ),
            'value' => $cart_item['true_stock_status']
        );
    }
    return $cart_data;
}
// Save true_stock_status value in order items meta data
add_action( 'woocommerce_add_order_item_meta', 'molswc_add_true_stock_status_to_order_item_meta', 20, 3 );
function molswc_add_true_stock_status_to_order_item_meta( $item_id, $values, $cart_item_key ) {
    if( isset( $values['true_stock_status'] ) )
        wc_add_order_item_meta( $item_id, __( 'Stock Status', 'woocommerce' ), $values['true_stock_status'] );
}

// === "Pending Inventory" custom orders status, create it, add it to WC Order Statuses list and assign it automatically for orders with products not in stock. 
// How about orders with 2 products, one in stock and the other one not? Though very rare, these are still possible! And currently gets the Pending Inventory status...
// Create "Pending Inventory" status first, as a normal post status
add_action( 'init', 'molswc_register_pending_inventory_order_status' );
function molswc_register_pending_inventory_order_status() {
    register_post_status( 'wc-pending-inventory', array(
        'label'                     => 'Pending inventory',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pending inventory <span class="count">(%s)</span>', 'Pending inventory <span class="count">(%s)</span>' )
    ) );
}
// Add "Pending Inventory" status to list of WC Order statuses
add_filter( 'wc_order_statuses', 'molswc_add_pending_inventory_to_order_statuses' );
function molswc_add_pending_inventory_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-pending-inventory'] = 'Pending inventory';
        }
    }
    return $new_order_statuses;
}
// Automatically assign Pending Inventory status to orders containing a product with True Stock Level other than "3", In_Stock
// Assigning this status at woocomemrce_thankyou - although the order should be splitted first then status applied only to non-3 ones ...
add_action('woocommerce_thankyou', 'molswc_auto_assign_pending_inventory_to_orders_based_on_stock_level');
function molswc_auto_assign_pending_inventory_to_orders_based_on_stock_level($order_id) {
	if ( ! $order_id ) {
		return;
	}
	global $product;
	$order = wc_get_order( $order_id );
	// Extract all products in the order, at variation level
	foreach ( $order->get_items() as $item_id => $item_values ) { // Iterating though each order items
		$working_var = $item_values['variation_id']; // getting the variation ID
		$current_var_id_true_stock_level = molswc_calculate_true_stock_level($working_var)['woo_stock_level']; // Get the stock level as set in WooCommerce
		if( $current_var_id_true_stock_level < 0 ) { // Check if stock level is lower than 0 ...
			$pending_inventory_control_variable = TRUE; // ... and switch the control variable to TRUE if stock is lower than 0
		}
	}
	// Now, IF control variable set above is TRUE then assign Pending Inventory status
	if ( $pending_inventory_control_variable ) { $order->update_status( 'wc-pending-inventory' ); }
	// In the end just unset the control variable 
	unset($pending_inventory_control_variable);
}

// === Putting order on hold with a properly formatted link (that would be used in email templates)
// a correct link would be: ...account/orders?nonce=NTQ4MzUy - the "nonce" value being a base64_encode of an existing order ID from the shop
add_action( 'woocommerce_before_customer_login_form', 'molswc_order_status_link_mgmt' ); // this will kick in if user isn't logged in
add_action( 'woocommerce_before_account_orders', 'molswc_order_status_link_mgmt' ); // otherwise this one will get triggered, above the orders list table
function molswc_order_status_link_mgmt(){
	// first make it work only in Orders section of Account page ... using strip_tags in an attempt to avoid malicious code in URL
	if ( strpos(strip_tags($_SERVER['REQUEST_URI']), 'account/orders') ) {
		// then decode the "nonce" to get the order ID
		$decoded_order_ID = base64_decode(strip_tags($_GET['nonce']));
		// Now check if that order ID actually exists
		if (get_post_type($decoded_order_ID) == "shop_order") {
			// so far so good, get the Order object
			$order = new WC_Order($decoded_order_ID);
			// record the status as we found it
			$ini_status = $order->get_status();
			// not all statuses should be convertible, so check it below
			if ($ini_status == 'processing' || $ini_status == 'pending-inventory') {
				// if ohr1 (aka Reason for status change) is defined, then proceed with status change
				if (strip_tags($_GET['ohr1']) !== '') {
					// let's compose the status change note in the first place
					$note = __("Status changed by using emailed link. Reason for on-hold: ".strip_tags($_GET['ohr1']).". Details: ".strip_tags($_GET['ohr2']));
					// change the staus, yey! (hardcoded to "on-hold" below, but we could make it variable so statuses can be managed by links alone)
					$order->update_status('on-hold', $note); 
					// record the status as resulted after the change (did the change worked? we can compare statuses)
					$new_status = $order->get_status();
					// now it's the time to notify the user that something happened, and what exactly
					echo '
					<h2>Order Hold</h2>
					<p>Order #'.$decoded_order_ID.' has been placed '.$new_status.' (was: '.$ini_status.').</p>
					<p>Reason for '.$new_status.': '.strip_tags($_GET["ohr1"]).'; <br>Details: '.strip_tags($_GET["ohr2"]).'</p>
					';
					// ... and if (s)he isn't logged in, show a quick invitation to login (login form is displayed afterwards anyway)
					if (!is_user_logged_in()) {
						echo '<p>You could also log in using the form below to view the complete order details:</p>';
					}
				// if ohr1 (aka Reason for status change) is NOT defined then go on and display the form
				} else {
					// Echo title and summary first
					echo '
					<h2>Order Hold</h2>
					<p>You are requesting to place order #'.$decoded_order_ID.' on hold. Orders that have already shipped cannot be placed on hold.</p>
					';
					// then check if form has been submitted, that means reason hasn't been provided, so we kindly ask for a reason
					if (strip_tags($_GET['submit']) == '1') {
						echo '<p style="color:yellow;">Please choose a reason for placing the order on hold.</p>';
						// ... and highlight that box with yellow, for an easy to regocnise action (will add this piece of style below in form anyway) 
						$reason_border = ' border-color:#ffff00;';
					}
					// now the time came to display the actual form
					echo '
					<form method="get" style="margin-bottom:40px;">
						<input type="hidden" name="nonce" value="'.strip_tags($_GET['nonce']).'">
						<input type="hidden" name="submit" value="1">
						<p style="margin-bottom: 2px;">Reason for hold:</p>
						<select name="ohr1" style="width: 100%; '.$reason_border.'">
							<option value="">Please select a reason</option>
							<option value="Address_correction">Address correction</option>
							<option value="Product_change">Product change</option>
							<option value="Cancel_order">Cancel order</option>
							<option value="Other">Other (please type the reason below)</option>
						</select>
						<p style="margin-bottom: 2px;">Details (optional):</p>
						<textarea rows="4" cols="50" name="ohr2">'.strip_tags($_GET['ohr2']).'</textarea>
					  <input type="submit" value="Submit" class="button" style="float:none;">
					</form>
					';
					// ... and again, if (s)he isn't logged in, show a quick invitation to login (login form is displayed afterwards anyway)
					if (!is_user_logged_in()) { 
						echo '<p>You could also log in using the form below to view the complete order details:</p>';
					}
				}
			}
		}
	}
}
// now let's send the On Hold email when status changes
add_action( 'woocommerce_order_status_processing_to_on-hold', 'molswc_enable_processing_to_on_hold_notification', 10, 2 ); // add the sending email action when changing Processing orders
add_action( 'woocommerce_order_status_pending-inventory_to_on-hold', 'molswc_enable_processing_to_on_hold_notification', 10, 2 ); // ... also add that action when changing Pending Inventory orders
function molswc_enable_processing_to_on_hold_notification( $order_id, $order ){
    // Getting all WC_emails array objects
    $mailer = WC()->mailer()->get_emails();
    // Finally send the "On Hold" notification
    $mailer['WC_Email_Customer_On_Hold_Order']->trigger( $order_id );
}

// === Fragment cache functions below
// A cache class used for product form content caching,
// Based on https://github.com/pressjitsu/fragment-cache/blob/master/fragment-cache.php
// Called in /Litheskateboards-Woocommerce-customizations/template/woocommerce/single-product/add-to-cart/variable.php, observe the parameters there
// Could be called anywhere else if needed :-)
class Pj_Fragment_Cache {
	private static $key;
	private static $args;
	private static $lock;
	public static function output( $key, $args = array() ) {
		if ( self::$lock )
			throw new Exception( 'Output started but previous output was not stored.' );
		$args = wp_parse_args( $args, array(
			'unique' => array(),
			'ttl' => 0,
		) );
		$args['unique'] = md5( json_encode( $args['unique'] ) );
		$args['prefix'] = 'molswc_cached_fragment_';
		$cache = self::_get( $key, $args );
		$serve_cache = true;
		if ( empty( $cache ) ) {
			$serve_cache = false;
		} elseif ( $args['ttl'] > 0 && $cache['timestamp'] < time() + $args['ttl'] ) {
			$serve_cache = false;
		} elseif ( ! hash_equals( $cache['unique'], $args['unique'] ) ) {
			$serve_cache = false;
		} elseif ( $args['disable'] ) {
			$serve_cache = false;
		}
		if ( ! $serve_cache ) {
			self::$key = $key;
			self::$args = $args;
			self::$lock = true;
			ob_start();
			return false;
		}
		echo '<!-- start cache block with key = '.$args['prefix'].$key.' -->'.$cache['data'].'<!-- end cache block with key = '.$args['prefix'].$key.' -->';
		return true;
	}
	private static function _get( $key, $args ) {
		$cache = null;
		$cache = get_option( $args['prefix'].$key );
		return $cache;
	}
	private static function _set( $key, $args, $value ) {
		$cache = add_option( $args['prefix'].$key, $value );
		return true;
	}
	public static function store() {
		if ( ! self::$lock )
			throw new Exception( 'Attempt to store but output was not started.' );
		self::$lock = false;
		$data = ob_get_clean();
		$cache = array(
			'data' => $data,
			'timestamp' => time(),
			'unique' => self::$args['unique'],
		);
		self::_set( self::$key, self::$args, $cache );
		echo $data;
	}
}
// Call the cached fragment deleting function to delete the fragments of the products that have just been purchased - based on partial name, including product ID
add_action( 'woocommerce_reduce_order_stock', 'molswc_delete_fragments_for_purchased_products' );
function molswc_delete_fragments_for_purchased_products($order) {
	foreach ( $order->get_items() as $item_id => $item_values ) {
		$current_product = $item_values['product_id'];
		$designated_fragment_string = 'prod_form_'.$current_product;
		molswc_delete_fragments( $designated_fragment_string );
	}
}
// A function for deleting the fragments based on partial name 
function molswc_delete_fragments( $fragment_partial_key ) {
    global $wpdb;
    $result = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%{$fragment_partial_key}%'" );
	return $result;
}
// A function to check if fragment(s) exist
function molswc_check_fragments( $fragment_partial_key ) {
    global $wpdb;
    $result = $wpdb->query( "SELECT * FROM {$wpdb->options} WHERE option_name LIKE '%{$fragment_partial_key}%'" );
	return $result;
}

// A function that deletes all product fragments if the right key is set as URL parameter
add_action ('init', 'molswc_fragments_purging_at_access');
function molswc_fragments_purging_at_access() {
	if ( strip_tags($_GET['fragdele']) == 'yes' ) {
		$delete_result = molswc_delete_fragments( 'molswc_cached_fragment_prod_form' ); 
	}
}

?>
