<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in product popup and in single product page, 2. have the last option show up only after selecting all previous ones, 3. jump directly to cart (checkout?) after selecting the last option. No settings page needed at this moment (but could be added later if needed). Works based on Quick View WooCommerce by XootiX for popup and on WooCommerce Variation Price Hints by Wisslogic for price calculations. For details/troubleshooting please contact me at https://moise.pro/contact/
 * Version: 0.1.34
 * Author: Alex Moise
 * Author URI: https://moise.pro
 */

if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// Adding own CSS
function molswc_adding_styles() {
	wp_register_style('lswc-styles', plugins_url('lswc.css', __FILE__));
	wp_enqueue_style('lswc-styles');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_styles', 99999999 ); // yeah, "avia-merged-styles" has 999999 :-P

// Adding own JS
function molswc_adding_scripts() {
	wp_register_script('lswc-script', plugins_url('lswc.js', __FILE__), array('jquery'), '', true);
	wp_enqueue_script('lswc-script');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_scripts', 9999999 ); 

// get rid of original JS from WC Variations Price Hints ... (for good, we won't replace it anymore as all functions are now in lswc.js)
function molswc_remove_wcvarhints_js() {
    wp_dequeue_script('wm_variation_price_hints_script');
    wp_deregister_script('wm_variation_price_hints_script');
}
add_action('wp_enqueue_scripts','molswc_remove_wcvarhints_js');

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

// Replace ATTRIBUTE TYPE variations buttons function of WC Variations Radio Buttons plugin, in order to add the *class needed to hook the variation price hints* JS
if ( ! function_exists( 'print_attribute_radio_attrib' ) ) {
	function print_attribute_radio_attrib( $checked_value, $value, $label, $name ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="attrib"><input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s /><label class="attrib option" value="%2$s" for="%3$s" data-text-fullname="%2$s" data-text-b="%5$s">%5$s</label></div>', $input_name, $esc_value, $id, $checked, $filtered_label );
	}
}

// Replace TAXONOMY TYPE variations buttons function of WC Variations Radio Buttons plugin, in order to add the *variation description*
if ( ! function_exists( 'print_attribute_radio_tax' ) ) {
	function print_attribute_radio_tax( $checked_value, $value, $label, $name, $attrib_description ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="tax"><input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s /><label class="tax option" value="%2$s" for="%3$s" data-text-fullname="%5$s" data-text-b="%5$s">%5$s</label><span class="attrib-description">%6$s</span></div>', $input_name, $esc_value, $id, $checked, $filtered_label, $attrib_description );
	}
}

// Move short description at the end of product page (at woocommerce_after_single_product_summary )
add_action('init', 'molswc_move_product_description');
function molswc_move_product_description() {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	// also remove XOO Product Popup actions:
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_meta', 30 );
	// also remove the regular image from popup ...
	remove_action('xoo-qv-images','xoo_qv_product_image',20);
	// and replace it with Product Smart Spinner:
	add_action( 'xoo-qv-images', array('SmartProductPlugin', 'wooCommerceImageAction'), 19 );
}

// Adjust (mostly remove) product details in product archive
add_action('init', 'molswc_change_product_in_archives');
function molswc_change_product_in_archives() {
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'avia_add_cart_button', 16 );
	// remove ordering and products per page
	remove_action( 'woocommerce_before_shop_loop', 'avia_woocommerce_frontend_search_params', 20 );
}

// Adding Mobile Scroll Hint Icon
add_action( 'xoo-qv-images', molswc_mobile_scroll_hint, 999 );
function molswc_mobile_scroll_hint () {
	if ( wp_is_mobile() ) {
		echo '
			<div class="scroll-hint center"><div class="mouse"><div class="wheel"></div></div>
			<div><span class="unu"></span><span class="doi"></span><span class="trei"></span></div></div>
		';
	}
}

// The product filter - pulling the attributes for adding them to product LI element
// add_action( 'woocommerce_before_shop_loop_item', molswc_test_variations_data ); // "woocommerce_before_shop_loop_item" is just before each item (good for debug)
add_action( 'molswc_product_li_additions', molswc_test_variations_data ); // "molswc_product_li_additions" is defined with a do_action in content-product.php in this plugin
function molswc_test_variations_data() {
	global $product; 
	$variations1=$product->get_children();
	foreach ($variations1 as $value) {
		$single_variation=new WC_Product_Variation($value);
		$var_is_purc = $single_variation->is_purchasable();
		$var_model_and_size = array_values($single_variation->get_variation_attributes())[0];
		if ( $var_is_purc == 1 ) { $data_custom_attribs_list_all[] = $var_model_and_size; }
	}
	$data_custom_attribs_list = ' data-custom-attribs-list="'.implode(",", array_unique($data_custom_attribs_list_all)).'"';
	echo $data_custom_attribs_list;
}

// Adding product filter drop down lists
add_action( 'woocommerce_before_shop_loop', 'molswc_product_filters', 30 );
function molswc_product_filters() {
	echo '
		<form id="product-filters" class="product-filters">
			<select name="Models">
			  <option value="" selected disabled hidden>Choose model</option>
			  <option value="Vert">Vert</option>
			  <option value="Street">Street</option>
			</select>
			<select name="Widths">
			  <option value="" selected disabled hidden>Choose width</option>
			  <option value="7.75">7.75</option>
			  <option value="7.88">7.88</option>
			  <option value="8.00">8.00</option>
			  <option value="8.13">8.13</option>
			  <option value="8.25">8.25</option>
			  <option value="8.38">8.38</option>
			  <option value="8.50">8.50</option>
			  <option value="8.75">8.75</option>
			  <option value="8.88">8.88</option>
			  <option value="9.00">9.00</option>
			</select>
			<a id="reset-product-filters" href="#/">Clear</a>
		</form>
	';
}
