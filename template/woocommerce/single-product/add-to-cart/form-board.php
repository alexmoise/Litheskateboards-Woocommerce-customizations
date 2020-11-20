<?php 

/**
 * Variation form for Board type products
 * 
 * Lithe version: 1.5.15
 * (version above is equal with main plugin file version when this file was updated)
 */

defined( 'ABSPATH' ) || exit;

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
						<div class="label td"><label class="product_column_title" for="<?php echo sanitize_title( $name ); ?>"><?php echo get_option( "molswc_product_options_table_header_text" ); ?></label></div>
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
										// Keep data of the attribute designated to generate an Add to Cart button. 
										// That should be only one, so care should be taken upstream while defining it!
										if ( in_array( $term->term_id, molswc_designated_addtocart_attributes() ) ) {
											$addtocart_attrib['id'] = $term->term_id;
											$addtocart_attrib['ckvalue'] = $checked_value;
											$addtocart_attrib['slug'] = $term->slug;
											$addtocart_attrib['name'] = $term->name;
											$addtocart_attrib['sanename'] = $sanitized_name;
											$addtocart_attrib['desc'] = $term->description;
										}
										print_attribute_radio_tax( $checked_value, $term->slug, $term->name, $sanitized_name, $term->description ); 
									}
									// Now go on and generate that Add to Cart button (with a special function)
									// print_attribute_radio_tax_addtocart( $addtocart_attrib['ckvalue'], $addtocart_attrib['slug'], $addtocart_attrib['name'], $addtocart_attrib['sanename'], $addtocart_attrib['desc'] );
								} else {
									$chosen_attribs = molswc_designated_options(); // defined in main PHP file of the plugin
									$all_attribs_string = implode("&",array_map(function($a) {return implode(" ",$a);},$attributes)); // create a string from all atributes multidimensional array
									foreach ( $chosen_attribs as $chosen_attrib ) { // iterate through all "designated options"
										if (strpos($all_attribs_string, $chosen_attrib) !== false) { // check if the current "designated option" is part of the current product variations, so we don't display empty lists
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