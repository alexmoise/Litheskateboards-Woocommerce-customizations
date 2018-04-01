<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in single product page, 2. have the last option show up only after selecting all previous ones, 3. jump directly to cart (checkout?) after selecting the last option. No settings page needed at this moment (but could be added later if needed). For details/troubleshooting please contact me at https://moise.pro/contact/
 * Version: 0.1.0
 * Author: Alex Moise
 * Author URI: https://moise.pro
 */

if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// Adding the JS
function molswc_adding_scripts() {
	wp_register_script('lswc-script', plugins_url('lswc.js', __FILE__), array('jquery'), true);
	wp_enqueue_script('lswc-script');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_scripts' ); 

// Adding the styles
function molswc_adding_styles() {
	wp_register_style('lswc-styles', plugins_url('lswc.css', __FILE__));
	wp_enqueue_style('lswc-styles');
}
add_action( 'wp_enqueue_scripts', 'molswc_adding_styles', 9999 );

