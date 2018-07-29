<?php
/**
 * Plugin Name: Litheskateboards Woocommerce customizations
 * Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * GitHub Plugin URI: https://github.com/alexmoise/Litheskateboards-Woocommerce-customizations
 * Description: A custom plugin to add some JS, CSS and PHP functions for Woocommerce customizations. Main goals are: 1. have product options displayed as buttons in product popup and in single product page, 2. have the last option (Payment Plan) show up only after selecting all previous ones, 3. jump directly to checkout after selecting the last option (Payment Plan). No settings page needed at this moment (but could be added later if needed). Works based on Quick View WooCommerce by XootiX for popup, on WooCommerce Variation Price Hints by Wisslogic for price calculations and also on WC Variations Radio Buttons for transforming selects into buttons. For details/troubleshooting please contact me at https://moise.pro/contact/
 * Version: 0.2.5
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

// Adding mobile app capability meta
add_action( 'wp_head', 'molswc_webapp_meta' ); 
function molswc_webapp_meta() {
    echo '<meta name="mobile-web-app-capable" content="yes">';
}

// Define the options to separate lists
function molswc_designated_options() {
	$designated_options = array('Street', 'Vert');
	return $designated_options;
}

// Get rid of original JS from WC Variations Price Hints ... (for good, we won't replace it anymore as all functions are now in lswc.js)
function molswc_remove_wcvarhints_js() {
    wp_dequeue_script('wm_variation_price_hints_script');
    wp_deregister_script('wm_variation_price_hints_script');
}
add_action('wp_enqueue_scripts','molswc_remove_wcvarhints_js');

// Go straight to Checkout when a Payment Method button has been pressed
add_filter('woocommerce_add_to_cart_redirect', 'molswc_go_to_checkout');
function molswc_go_to_checkout() {
	global $woocommerce;
	$checkout_url = wc_get_checkout_url();
	return $checkout_url;
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

// Various Woocommerce layout adjustments here:
add_action('init', 'molswc_layout_adjustments');
function molswc_layout_adjustments() {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 ); // remove short description from original place ...
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_excerpt', 20 ); // ... and add it again at the end of product page (at woocommerce_after_single_product_summary )
	// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 ); // remove the title from its original location ...
	// add_action( 'woocommerce_before_main_content', 'woocommerce_template_single_title', 10 ); // ... and add it back on top of the page. *** NO HOOK ON POPUP THOUGH, SO WE CAN'T MOVE IT THERE ***
	remove_action( 'xoo-qv-images','xoo_qv_product_image',20); // also remove the regular image from popup ...
	add_action( 'xoo-qv-images', array('SmartProductPlugin', 'wooCommerceImageAction'), 19 ); // ... and replace it with Product Smart Spinner:
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_rating', 10 ); // remove XOO Product Popup actions: rating
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_excerpt', 20 ); // remove XOO Product Popup actions: excerpt
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_meta', 30 ); // remove XOO Product Popup actions: meta
	remove_action( 'xoo-qv-summary', 'woocommerce_template_single_price', 15 ); // remove XOO Product Popup actions: price range
	remove_action( 'woocommerce_after_single_product_summary','avia_woocommerce_output_related_products',20); // remove related products in single product page:
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'avia_add_cart_button', 16 );
	remove_action( 'woocommerce_before_shop_loop', 'avia_woocommerce_frontend_search_params', 20 ); // remove ordering and products per page
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 ); // get rid of sale flash
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 ); // no more SKU and Cats on product page
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 ); // remove price range after title in single product *but not in popup*
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

// The product filter - pulling the attributes for adding them to product LI element. Used in "content-product.php" file in this plugin
// add_action( 'woocommerce_before_shop_loop_item', molswc_test_variations_data ); // "woocommerce_before_shop_loop_item" is just before each item (good for debug)
function molswc_test_variations_data() {
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
	$complete_unique_list_models_and_sizes = array_unique($unique_models_and_sizes);
	$final_models_and_sizes_list = molswc_flat_array($complete_unique_list_models_and_sizes);
	$chosen_attribs = molswc_designated_options(); // sync this later with Woocommerce ... or easily define these some other way ...
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
			  <option value="" selected disabled hidden>Choose model</option>';
			  foreach ( $chosen_attribs as $chosen_attrib ) { 
				echo '<option value="'.$chosen_attrib.'">'.$chosen_attrib.'</option>';
			  }
	echo '  </select>
			<select name="Widths">
			  <option value="" selected disabled hidden>Choose width</option>';
			  foreach ( $unique_only_widths as $width ) { 
				echo '<option value="'.$width.'">'.$width.'</option>';
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

// Sync peer variations stock at placing new order. (peer variations are those which have the sane 'model-and-size' attribute but different 'payment-plan' global attribute)
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
			$current_var_model_and_size = $attribs_array['model-and-size']; // ...and get only the "model-and-size"
			$parent_id = $current_var->get_parent_id(); // Get the ID of the parent product
			$parent_product = wc_get_product( $parent_id ); // Get an instance of parent product
			$peer_variations = $parent_product->get_available_variations(); // Now get all the *possible* peer variations, those that are chlidren of the same product 
			// Let's iterate though each *possible* peer variation:
			foreach ($peer_variations as $peer_variation) {
				$peer_variation_id      = $peer_variation['variation_id']; // this is peer variation ID
				$peer_variation_product = wc_get_product( $peer_variation_id ); // this is peer variation product instance
				$peer_variation_attribs = wc_get_formatted_variation( $peer_variation_product->get_variation_attributes(), true ); // these are peer variation attributes
				parse_str(strtr($peer_variation_attribs, ":,", "=&"), $peer_var_attribs_array); // this is peer variation attributes *array*
				$peer_var_attrib_model_and_size = $peer_var_attribs_array['model-and-size']; // this is peer variation 'model-and-size' attribute
				if ($current_var_model_and_size == $peer_var_attrib_model_and_size) { // check if this *IS* a peer variation (has same Model-and-Size)
					// Start creating the $peer_vars_working_array to store the IDs of variations that wold be synced later
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
