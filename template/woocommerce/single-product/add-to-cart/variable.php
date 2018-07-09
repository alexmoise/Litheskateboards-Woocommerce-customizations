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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$attribute_keys = array_keys( $attributes );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo htmlspecialchars( wp_json_encode( $available_variations ) ) ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<div id="table-variations" class="table variations" cellspacing="0">
			<div class="tbody">
				<?php foreach ( $attributes as $name => $options ) : ?>
					<div class="select attribute-<?php echo sanitize_title($name); ?> tr">
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
										echo '<div class="value td">'; // adding values / radio -> buttons wrapper
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

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
