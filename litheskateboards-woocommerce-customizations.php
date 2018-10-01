<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in product popup and in single product page, 2. have the last option (Payment Plan) show up only after selecting a Width corresponding to a Model, 3. jump directly to checkout after selecting the last option (Payment Plan). Works based on Quick View WooCommerce by XootiX for popup, on WooCommerce Variation Price Hints by Wisslogic for price calculations and also on WC Variations Radio Buttons for transforming selects into buttons. For details/troubleshooting please contact me at <a href="https://moise.pro/contact/">https://moise.pro/contact/</a>
 * Version: 1.0.34
 * Author: Alex Moise
 * Author URI: https://moise.pro
 */

if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// Adding admin options
include( plugin_dir_path( __FILE__ ) . 'lswc-options.php' );

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

// Adding the Settings link in Plugins Page, next to Deactivate link
function molswc_plugin_action_links( $molswclinks ) {
	$molswclinks = array_merge( array(
		'<a target="_blank" href="' . esc_url( admin_url( '/admin.php?page=lithe-options' ) ) . '">' . __( 'Settings' ) . '</a>'
	), $molswclinks );
	return $molswclinks;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'molswc_plugin_action_links' );

// Adding mobile app capability meta
add_action( 'wp_head', 'molswc_webapp_meta' ); 
function molswc_webapp_meta() {
    echo '<meta name="mobile-web-app-capable" content="yes">';
}

// Debug variable to file; precede it with a label for better understanding
function molswc_debug_to_file($output_label, $something_to_output) {
	$timestamp = date("Y-m-d H:i:s");
	if ( defined('ABSPATH') ) { $path_to_wp_content = ABSPATH.'wp-content/'; $molswc_file = $path_to_wp_content.'testdata.txt';} else { return; }
	file_put_contents($molswc_file, "\n".$timestamp.": ### Debug_to_file triggered ### ", FILE_APPEND | LOCK_EX);
	$readable_thing = print_r($something_to_output, true); 
	file_put_contents($molswc_file, "\n".$timestamp.": ".$output_label." = ".$readable_thing."  ", FILE_APPEND | LOCK_EX);	
}

// Just another simple trim function, for later use :-)
function molswc_trim_value(&$value) { $value = trim($value); } 

// Define the options to separate lists
function molswc_designated_options() {
	$raw_designated_options = strip_tags(get_option( 'molswc_designated_options' )); // get raw options as defined in options DB table
	$designated_options = explode(',', $raw_designated_options); // create an array with options
	array_walk($designated_options, 'molswc_trim_value'); // remove possible white space at the beginning or the end of each array element (using previously defined trim function)
	return $designated_options; // finally return the array to wherever is needed
}

// Exclude the products in these categories from displaying on the shop page
add_action( 'woocommerce_product_query', 'molswc_custom_boards_query' );
function molswc_custom_boards_query( $q ) {
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

// Get rid of original JS from WC Variations Price Hints ... (for good, we won't replace it anymore as all functions are now in lswc.js)
add_action('wp_print_scripts','molswc_remove_wcvarhints_js');
function molswc_remove_wcvarhints_js() {
	if ( !is_shop() ) { // we need it in shop because here there's no "current" product, unless popup pops up with one
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

// Shop page title changed to "Shop - Lithe Skateboards"
add_filter('pre_get_document_title', 'molswc_shop_title_tag');
function molswc_shop_title_tag(){
	if ( is_shop() ) {
		return 'Shop - Lithe Skateboards';
	}
}

// Go straight to Checkout when a Payment Method button has been pressed
add_filter('woocommerce_add_to_cart_redirect', 'molswc_go_to_checkout');
function molswc_go_to_checkout() {
	global $woocommerce;
	$checkout_url = wc_get_checkout_url();
	return $checkout_url;
}

// Enable Avia Builder Debug, for easily copy/paste page contents
add_action('avia_builder_mode', "molswc_builder_set_debug");
if ( ! function_exists( 'molswc_builder_set_debug' ) ) {
	function molswc_builder_set_debug() {
		if ( get_option( 'molswc_enable_avia_debug' ) ) { return "debug"; }
	}
}

// Extend conditional variations limit
add_filter( 'woocommerce_ajax_variation_threshold', 'molswc_wc_ajax_variation_threshold', 10, 2 );
function molswc_wc_ajax_variation_threshold( $qty, $product ) {
    return 50;
}

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
		$parent_product_id = $product->get_id(); // getting the parent product ID
		$id = esc_attr( $name . '_v_' . $value . '_p_'. $parent_product_id ); // added product ID at the end of the name to target single products
		$checked = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		$peer_vars = molswc_get_peer_variations($parent_product_id, $name, $value); // getting the peer variations (see the function for details)
		if ($peer_vars) { // Now, if there's any peer_vars found ...
			$peer_var_non_subscription_available = 'no'; // ... start assuming there's no non-subscription product available ...
			foreach ($peer_vars as $peer_var) { // ... then iterate through all peer_vars ...
				$peer_var_stock[$peer_var] = molswc_get_true_stock_status($peer_var)['true_stock_status']; // ... and get the stock for each peer variation ...
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
		printf( '<div class="tax" data-text-name="%2$s"><input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s /><label class="tax option" value="%2$s" for="%3$s" data-text-fullname="%5$s" data-text-b="%5$s">%5$s</label><span class="attrib-description">%6$s</span></div>', $input_name, $esc_value, $id, $checked, $filtered_label, $attrib_description );
	}
}

// Various Woocommerce layout adjustments below:
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
	remove_action( 'woocommerce_after_single_product_summary','avia_woocommerce_output_related_products',20); // remove related products in single product page:
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'avia_add_cart_button', 16 );
	remove_action( 'woocommerce_before_shop_loop', 'avia_woocommerce_frontend_search_params', 20 ); // remove ordering and products per page
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 ); // get rid of sale flash
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 ); // no more SKU and Cats on product page
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 ); // remove price range after title in single product *but not in popup*
	remove_action( 'woocommerce_product_tabs', 'woocommerce_default_product_tabs', 10 );

}
add_action('wp', 'molswc_advanced_layout_adjustments');
function molswc_advanced_layout_adjustments() {
	if (is_product()) { // only in product page, otherwise breaks the boards list archive,
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 ); // remove the title from its original location ...
		add_action( 'woocommerce_before_main_content', 'woocommerce_template_single_title', 10 ); // ... and add it back on top of the page. *** NO HOOK ON POPUP THOUGH, SO WE CAN'T MOVE IT THERE ***
	}
}

// Adding Mobile Scroll Hint Icon
add_action( 'xoo-qv-images', 'molswc_mobile_scroll_hint', 999 );
function molswc_mobile_scroll_hint () {
	if ( wp_is_mobile() ) {
		echo '
			<div class="scroll-hint center"><div class="mouse"><div class="wheel"></div></div>
			<div><span class="unu"></span><span class="doi"></span><span class="trei"></span></div></div>
		';
	}
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

// The product filter - pulling the attributes for adding them to product LI element. Used in "content-product.php" file in this plugin
// add_action( 'woocommerce_before_shop_loop_item', molswc_instock_variations ); // "woocommerce_before_shop_loop_item" is just before each item (good for debug)
function molswc_instock_variations() {
	global $product; 
	$variations1=$product->get_children();
	foreach ($variations1 as $value) {
		$single_variation=new WC_Product_Variation($value);
		$var_is_purc = $single_variation->is_purchasable();
		$var_has_stock = $single_variation->get_stock_quantity(); 
		if ( $var_is_purc == 1 && $var_has_stock > 0) { 
			$var_model_and_size = array_values($single_variation->get_variation_attributes())[0];
			$data_custom_attribs_list_all[] = $var_model_and_size; 
		}
	}
	if($data_custom_attribs_list_all) { $data_custom_attribs_list = array_unique($data_custom_attribs_list_all); }
	return $data_custom_attribs_list;
}
// Adding product filter drop down lists
add_action( 'woocommerce_before_shop_loop', 'molswc_product_filters', 30 );
function molswc_product_filters() {
	// querying all the boards in the "Decks" category:
	$boards_IDs = new WP_Query( array(
	'post_type' => 'product',
	'post_status' => 'publish',
	'fields' => 'ids', 
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'field' => 'term_id',
				'terms' => '7', // '7' is the "Decks" category
				'operator' => 'IN',
			)
		)
	) );
	$all_boards = $boards_IDs->posts;
	// pulling the variations of each board:
	foreach ($all_boards as $single_board) {
		$product = wc_get_product($single_board);
		$board_variations = $product->get_children();
		foreach ($board_variations as $board_variation) {
			$single_variation=new WC_Product_Variation($board_variation);
			$var_model_and_size = array_values($single_variation->get_variation_attributes())[0];
			$all_model_and_sizes[] = $var_model_and_size; 
		}
		$unique_models_and_sizes[] = array_unique($all_model_and_sizes);
	}
	$flat_models_and_sizes_list = molswc_flat_array($unique_models_and_sizes);
	$final_models_and_sizes_list = array_unique($flat_models_and_sizes_list); 
	$chosen_attribs = molswc_designated_options();
	foreach ( $chosen_attribs as $chosen_attrib ) { 
		$only_widths[] = str_replace($chosen_attrib." ", "", $final_models_and_sizes_list);
	}
	$flat_only_widths = molswc_flat_array($only_widths);
	foreach ( $chosen_attribs as $chosen_attrib ) { 
		foreach ($flat_only_widths as $key => $width) {
			if (strpos($width,$chosen_attrib) !== false) {
				unset($flat_only_widths[$key]);
			}
		}
	}
	$unique_only_widths = array_unique($flat_only_widths);
	sort($unique_only_widths);
	echo '
		<form id="product-filters" class="product-filters">
			<select name="Models">
			  <option value="" selected disabled hidden>All Models</option>';
			  foreach ( $chosen_attribs as $chosen_attrib ) { 
				echo '<option value="'.$chosen_attrib.'" disabled="disabled">'.$chosen_attrib.'</option>';
			  }
	echo '  </select>
			<select name="Widths">
			  <option value="" selected disabled hidden>All Widths</option>';
			  foreach ( $unique_only_widths as $width ) { 
				echo '<option value="'.$width.'" disabled="disabled">'.$width.'</option>';
			  }
	echo '  </select>
			<a id="reset-product-filters" href="#/">Clear</a>
		</form>
	';
}
// Flatten that array ... (used a couple of times in the function above)
function molswc_flat_array(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

// Rack bottom and Rack top images in main shop page
// Adding Rack Top:
add_action( 'woocommerce_before_shop_loop', 'molswc_rack_top_image', 40 );
function molswc_rack_top_image() {
	$top_image_file_url = plugins_url('images/Rack_Top.png', __FILE__);
	echo '
	<div class="rack_top">
		<img class="rack_top_image" src="'.$top_image_file_url.'" alt="Lithe Skateboards Rack Top">
	</div>';
}
// Adding Rack Bottom:
add_action( 'woocommerce_after_shop_loop', 'molswc_rack_bottom_image', 0 );
function molswc_rack_bottom_image() {
	$bottom_image_file_url = plugins_url('images/Rack_Bottom.png', __FILE__);
	echo '
	<div class="rack_bottom">
		<img class="rack_bottom_image" src="'.$bottom_image_file_url.'" alt="Lithe Skateboards Rack Bottom">
	</div>';
}

// User subscription-able
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

// Sync peer variations stock at placing new order. (peer variations are those which have the same 'model-and-size' attribute but different 'payment-plan' global attribute)
add_action( 'woocommerce_reduce_order_stock', array('Wc_class', 'molswc_stock_adjutments'));
class Wc_class {
	public static function molswc_stock_adjutments($order) {
		foreach ( $order->get_items() as $item_id => $item_values ) { // Iterating though each order items
			$item_id = $item_values->get_id(); // The item ID
			$item_qty = $item_values['qty']; // Item quantity
			$current_var_id = $item_values['variation_id']; // current variation ID 
			$current_var = wc_get_product( $current_var_id ); // Get an instance of the current variation object
			$current_var_stock = $current_var->get_stock_quantity(); // Get the stock quantity of the current_var
			$current_var_attribs = wc_get_formatted_variation( $current_var->get_variation_attributes(), true ); // Get the attributes of the current variation
			parse_str(strtr($current_var_attribs, ":,", "=&"), $attribs_array); // parse the attributes object ...
			$attrib_to_use_for_peering = molswc_check_if_custom_attrib_exists($attribs_array); // now check which attribute we may use for peering ...
			$current_var_peering_attrib = $attribs_array[$attrib_to_use_for_peering]; // ...and get only that special model (based on an array defined in the function called above - maybe change that later to Admin?)
			$parent_id = $current_var->get_parent_id(); // Get the ID of the parent product
			$parent_product = wc_get_product( $parent_id ); // Get an instance of parent product (***** later maybe switch to use "molswc_get_peer_variations" function from here *****)
			$peer_variations = $parent_product->get_available_variations(); // Now get all the *possible* peer variations, those that are children of the same product 
			// Let's iterate though each *possible* peer variation:
			foreach ($peer_variations as $peer_variation) {
				$peer_variation_id      = $peer_variation['variation_id']; // this is peer variation ID
				$peer_variation_product = wc_get_product( $peer_variation_id ); // this is peer variation product instance
				$peer_variation_attribs = wc_get_formatted_variation( $peer_variation_product->get_variation_attributes(), true ); // these are peer variation attributes
				parse_str(strtr($peer_variation_attribs, ":,", "=&"), $peer_var_attribs_array); // this is peer variation attributes *array*
				$peer_var_peering_attrib = $peer_var_attribs_array[$attrib_to_use_for_peering]; // this is peer variation 'model-and-size' attribute
				if ($current_var_peering_attrib == $peer_var_peering_attrib) { // check if this *IS* a peer variation (has same Model-and-Size or so)
					// Start creating the $peer_vars_working_array to store the IDs of variations that would be synced later
					if ( !isset($peer_vars_working_array[$peer_variation_id]) ) { // if this peer variations is not in working array ...
						$peer_vars_working_array[$peer_variation_id] = $current_var_stock; // ...add it with ID as index and current stock as value
					} else { // otherwise ...
						$new_peer_var_stock_value = $peer_vars_working_array[$peer_variation_id] - $item_qty; // ...subtract current variation quantity from the value stored in the working array ...
						$peer_vars_working_array[$peer_variation_id] = $new_peer_var_stock_value; // ...and store the new value in the working array
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
	$possible_custom_attribs = array('model-and-size', 'model-width'); // define these in Admin Page if anything. Shouldn't doing it tho ... 
	foreach ($possible_custom_attribs as $possible_custom_attrib) {
		if (array_key_exists($possible_custom_attrib, $attribs_array)) {
			return $possible_custom_attrib;
		}
	}
}

// Get peer variations: loop through all children of a parent product by ID and return an array with all variations having same "...attr_value" into "...attr_name".
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

// Outputting JS variables in HTML, for using them later
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

// Prevent Left/Right product hints to show in product pages. Could change its settings and made it show, see it in parent theme, in "functions-enfold.php" file around line 505
if(!function_exists('avia_post_nav')) {
	function avia_post_nav($same_category = false, $taxonomy = 'category') { return; }
}

// Fragment cache class used for popup content caching,
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
		$cache = add_option( $args['prefix'].$key, $value, $args['ttl'] );
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
} // done fragment caching.

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

// === Backorder and True Stock Levels functions
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

// Save back order stock level when saving the Product Edit screen or only the Variations
add_action( 'woocommerce_save_product_variation', 'molswc_save_variation_backorder_stock_level', 10, 2 );
function molswc_save_variation_backorder_stock_level( $post_id ) {
	$number_field = $_POST['backorder_stock_level'][ $post_id ];
	if( ! empty( $number_field ) ) {
		update_post_meta( $post_id, 'backorder_stock_level', esc_attr( $number_field ) );
	}
}

// Store back order stock level in variation meta data, so it gets outputted in the variations_form
add_filter( 'woocommerce_available_variation', 'molswc_store_variation_backorder_stock_level' );
function molswc_store_variation_backorder_stock_level( $variations ) {
    $variations['backorder_stock_level'] = get_post_meta( $variations[ 'variation_id' ], 'backorder_stock_level', true );
    return $variations;
}

// Calculate true stock LEVEL by variation ID
// Returns an array, check the items below to pick them
// Use: $truelevel = molswc_get_true_stock_level($variation_id)['true_stock_level'];
function molswc_get_true_stock_level($variation_id) {
	$variation_instance = wc_get_product( $variation_id ); // Get an instance of the current variation object
	$true_stock_data['woo_stock_level'] = $variation_instance->get_stock_quantity(); // Get the stock quantity of the current_var
	$true_stock_data['backorder_stock_level'] = get_post_meta( $variation_id, 'backorder_stock_level', true ); // Get the backorder_stock_level of the current_var
	$true_stock_data['true_stock_level'] = $true_stock_data['woo_stock_level'] + $true_stock_data['backorder_stock_level']; // Calculate true stock level
	return $true_stock_data;
}

// Calculate true stock STATUS by variation ID
// Returns an array, check the items below to pick them
// Use: $truestatus = molswc_get_true_stock_status($variation_id)['true_stock_status'];
// Get: 1 = 'true_preorder' OR 2 = 'true_backorder'; OR 3 = 'true_instock';
function molswc_get_true_stock_status($variation_id) {
	$true_stock_data = molswc_get_true_stock_level($variation_id);
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

// Set *and sync!* the right pre order status of variation at purchase level
add_action( 'woocommerce_reduce_order_stock', array('Wc_class_preorder_adjustments', 'molswc_adjust_preorder_status'));
class Wc_class_preorder_adjustments {
	public static function molswc_adjust_preorder_status($order) {
		foreach ( $order->get_items() as $item_id => $item_values ) { // Iterating though each order items
			$item_id = $item_values->get_id(); // The item ID
			$current_var_id = $item_values['variation_id']; // current variation ID 
			$current_var = wc_get_product( $current_var_id ); // Get an instance of the current variation object
			$parent_id = $current_var->get_parent_id(); // Get the ID of the parent product
			$current_var_attribs = wc_get_formatted_variation( $current_var->get_variation_attributes(), true ); // Get the attributes of the current variation
			parse_str(strtr($current_var_attribs, ":,", "=&"), $attribs_array); // parse the attributes object ...
			$attrib_to_use_for_peering = molswc_check_if_custom_attrib_exists($attribs_array); // now check which attribute we may use for peering ...
			$attrib_values = $current_var -> attributes; // ... then get all values of the attribute used for peering ...
			$value_to_use_for_peering = $attrib_values[$attrib_to_use_for_peering]; // ... and get the exact value we need for peeting.
			$peer_vars = molswc_get_peer_variations($parent_id, $attrib_to_use_for_peering, $value_to_use_for_peering); // Now, get all peer variations based on data we retrieved before
			foreach ( $peer_vars as $peer_var ) {
				$peer_vars_working_array[] = $peer_var; // and add each of them to working array that we'll process later
			}
		}
		// Now process the working array, applying the changes
		foreach ( $peer_vars_working_array as $working_var ) {
			$current_var_id_true_stock_status = molswc_get_true_stock_status($working_var)['true_stock_status']; 
			if( $current_var_id_true_stock_status == 1 ) {
				molswc_set_preorder_status($working_var, 'yes');
			} else {
				molswc_set_preorder_status($working_var, 'no');
			}
		}
	}
}

?>
