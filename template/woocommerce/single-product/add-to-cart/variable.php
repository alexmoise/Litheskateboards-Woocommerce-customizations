<?php
/**
 * Variable product add to cart 
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.5.0
 *
 * Modified to use radio buttons instead of dropdowns
 * @author 8manos
 * 
 * Lithe version: 1.0.25
 * (version above is equal with main plugin file version when this file was updated)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$attribute_keys = array_keys( $attributes );

do_action( 'woocommerce_before_add_to_cart_form' ); 

// building the cache args array
$fragm_cache_args = array();
// $fragm_cache_args['disable'] = 'true'; // could disable cache completely, for debug purposes

// building a unique cache key here: add conditions below so the key changes when the form content should change; the order of elements is important too
$fragm_cache_key_build['identifier'] = 'prod_form'; // set a unique name at the beginning of the transient, we'll use this later do delete these transients
$fragm_cache_key_build['productid'] = $product->get_id(); // add product ID in the mix, so the product forms does not mix :-)
$fragm_cache_key_build['isproduct'] = is_product() ?: 0; // check if is a product page, because the popup access creates the form without the "wm_pvar" data
$fragm_cache_key_build['usersubscript'] = molswc_check_user_subscription_able(); // add user subscription-able condition to the key, because these users have different prices
// $fragm_cache_key_build['userlogged'] = is_user_logged_in() ?: 0; // check if there's a user authenticated, otherwise return 0

$fragm_cache_key = implode('_', $fragm_cache_key_build); // defining a unique key for caching the forms uniquely for each board, user status, etc.
if ( !Pj_Fragment_Cache::output( $fragm_cache_key, $fragm_cache_args ) ) { // conditionally call the cache output right here before building the whole <form>:

?>

<form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo htmlspecialchars( wp_json_encode( $available_variations ) ) ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<div id="table-variations" class="table variations" cellspacing="0">
			<div class="tbody">
				<?php foreach ( $attributes as $name => $options ) : ?>
					<div class="select attribute-<?php echo sanitize_title($name); if ( taxonomy_exists( $name ) ) { echo ' tax_attrib'; } else { echo ' custom_attrib'; } ?> tr">
						<div class="label td"><label for="<?php echo sanitize_title( $name ); ?>"><?php echo wc_attribute_label( $name ); ?></label></div>
						<?php
						$sanitized_name = sanitize_title( $name );
						if ( isset( $_REQUEST[ 'attribute_' . $sanitized_name ] ) ) {
							$checked_value = $_REQUEST[ 'attribute_' . $sanitized_name ];
						} elseif ( isset( $selected_attributes[ $sanitized_name ] ) ) {
							$checked_value = $selected_attributes[ $sanitized_name ];
						} else {
							$checked_value = '';
						}
						?>
						<div class="value td">
							<?php
							if ( ! empty( $options ) ) {
								if ( taxonomy_exists( $name ) ) {
									// Get terms if this is a taxonomy - ordered. We need the names too.
									$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) ); 
									foreach ( $terms as $term ) { 
										if ( ! in_array( $term->slug, $options ) ) {
											continue;
										}
										print_attribute_radio_tax( $checked_value, $term->slug, $term->name, $sanitized_name, $term->description ); 
									}
								} else {
									$chosen_attribs = molswc_designated_options(); // defined in main PHP file of the plugin
									foreach ( $chosen_attribs as $chosen_attrib ) { 
										$each_attribs = array_filter($options, function($var) use ($chosen_attrib) { return preg_match("/\b$chosen_attrib\b/i", $var); });
										echo '<div class="each-attrib chosen-attrib-'.$chosen_attrib.'">'; // open the div wrapper for each attribute list
										echo '<div class="label td"><label for="'.$chosen_attrib.'">'.$chosen_attrib.'</label></div>'; // adding attribute list label / title
										echo '<div class="value-buttons td">'; // adding values / radio -> buttons wrapper
										foreach ( $each_attribs as $each_attrib ) { 
											$attrib_label = str_replace($chosen_attrib." ", "", $each_attrib); 
											print_attribute_radio_attrib( $checked_value, $each_attrib, $attrib_label, $sanitized_name );
										}
										echo '</div>'; //closing the div wrapper for values / radio -> buttons wrapper
										echo '</div>'; //closing the div wrapper for each attribute list
									}
								}
							}
							
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php echo end( $attribute_keys ) === $name ? apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . __( 'Clear', 'woocommerce' ) . '</a>' ) : ''; ?>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="single_variation_wrap">
			<?php
				do_action( 'woocommerce_before_single_variation' );
				do_action( 'woocommerce_single_variation' );
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php 

Pj_Fragment_Cache::store();
} // Since the <form> is built at this moment let's close fragment cache call here.

do_action( 'woocommerce_after_add_to_cart_form' ); 

?>
