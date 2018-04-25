<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in single product page, 2. have the last option show up only after selecting all previous ones, 3. jump directly to cart (checkout?) after selecting the last option. No settings page needed at this moment (but could be added later if needed). For details/troubleshooting please contact me at https://moise.pro/contact/
 * Version: 0.1.17
 * Author: Alex Moise
 * Author URI: https://moise.pro
 */

if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// Adding the JS
function molswc_adding_scripts() {
	wp_register_script('lswc-script', plugins_url('lswc.js', __FILE__), array('jquery'), '', true);
	wp_enqueue_script('lswc-script');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_scripts', 10 ); 

// Adding the styles
function molswc_adding_styles() {
	wp_register_style('lswc-styles', plugins_url('lswc.css', __FILE__));
	wp_enqueue_style('lswc-styles');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_styles', 9999999 ); // yeah, "avia-merged-styles" has 999999 :-P

// Override WooCommerce Template and Parts
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
	// echo '<br>Overr path: '.$plugin_path . $template_name.'<br>';
	// echo '<br>_Template path: '.$_template.'<br>';
	return $template;
}

// Move short description at the end of product page (at woocommerce_after_single_product_summary )
add_action('init', 'molswc_move_product_description');
function molswc_move_product_description() {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
}

// Replace TAXONOMY TYPE variations buttons function, in order to add the variation description
if ( ! function_exists( 'print_attribute_radio_tax' ) ) {
	function print_attribute_radio_tax( $checked_value, $value, $label, $name, $attrib_description ) {
		global $product;
		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="select tax"><input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s /><label class="option tax" selected="" for="%3$s" data-text-b="%5$s">%5$s</label><span class="attrib-description">%6$s</span></div>', $input_name, $esc_value, $id, $checked, $filtered_label, $attrib_description );
	}
}

// Replace ATTRIBUTE TYPE variations buttons function, in order to add the class needed to hook the variation price JS
if ( ! function_exists( 'print_attribute_radio_attrib' ) ) {
	function print_attribute_radio_attrib( $checked_value, $value, $label, $name ) {
		global $product;

		$input_name = 'attribute_' . esc_attr( $name ) ;
		$esc_value = esc_attr( $value );
		$id = esc_attr( $name . '_v_' . $value . $product->get_id() ); //added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		printf( '<div class="select attrib"><input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s /><label class="option attrib" selected="" for="%3$s" data-text-b="%5$s">%5$s</label></div>', $input_name, $esc_value, $id, $checked, $filtered_label );
	}
}


// Add prices in buttons (BUT beware that it's pulled on the server, so it doesn't know the chosen variation)
// add_filter( 'woocommerce_variation_option_name', 'display_price_in_variation_option_name' );
/* 
function display_price_in_variation_option_name( $term ) {
	global $wpdb, $product;
	$result = $wpdb->get_col( "SELECT slug FROM {$wpdb->prefix}terms WHERE name = '$term'" );
	$term_slug = ( !empty( $result ) ) ? $result[0] : $term;

	$query = "SELECT postmeta.post_id AS product_id
	FROM {$wpdb->prefix}postmeta AS postmeta
	LEFT JOIN {$wpdb->prefix}posts AS products ON ( products.ID = postmeta.post_id )
	WHERE postmeta.meta_key LIKE 'attribute_%'
	AND postmeta.meta_value = '$term_slug'
	AND products.post_parent = $product->id";

	$variation_id = $wpdb->get_col( $query );
	$parent = wp_get_post_parent_id( $variation_id[0] );

	if ( $parent > 0 ) {
		$_product = new WC_Product_Variation( $variation_id[0] ); echo '<!-- VarID: '; print_r($variation_id[0]); echo' -->';
		$itemPrice = strip_tags (WC_price( $_product->get_price() ));
		$itemDescription = $_product->get_variation_attributes(); echo '<!-- DESC: '; print_r($itemDescription); echo' -->';
		return $term . ' (' . $itemPrice . ')'; // customize how the price is displayed here
	}
	
	return $term;
}  */

