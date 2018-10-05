<?php
/**
 * Settings Page for Litheskateboards Woocommerce customizations
 * Version: 1.1.4
 * (version above is equal with main plugin file version when this file was updated)
 */
if ( ! defined( 'ABSPATH' ) ) {	exit(0);}

// Adding the menu item under Woocommerce and create the associated options page
add_action('admin_menu', 'molswc_register_admin_options_page', 99);
function molswc_register_admin_options_page() {
    add_submenu_page( 'woocommerce', 'Lithe Shop Options', 'Lithe Shop Options', 'manage_options', 'lithe-options', 'molswc_admin_options_page_callback' ); 
}

// Register the settings here
add_action( 'admin_init', 'molswc_register_settings' );
function molswc_register_settings() {
	// First define the options (beware there's a copy of this array in uninstall.php for the moment)
	$molswc_settings_array = array(
		'molswc_estdelivery_instock',
		'molswc_estdelivery_backorder',
		'molswc_estdelivery_preorder',
		'molswc_instock_label',
		'molswc_backorder_label',
		'molswc_preorder_label',
		'molswc_notavailable_label',
		'molswc_pre_order_message',
		'molswc_designated_options',
		'molswc_excluded_categories',
		'molswc_enable_avia_debug',
		'molswc_transient_keys_purging',
		'molswc_instock_label_color',
		'molswc_instock_label_hover_color',
		'molswc_instock_border_color',
		'molswc_instock_border_hover_color',
		'molswc_backorder_label_color',
		'molswc_backorder_label_hover_color',
		'molswc_backorder_border_color',
		'molswc_backorder_border_hover_color',
		'molswc_preorder_label_color',
		'molswc_preorder_label_hover_color',
		'molswc_preorder_border_color',
		'molswc_preorder_border_hover_color',
		'molswc_notavailable_label_color',
		'molswc_notavailable_label_hover_color',
		'molswc_notavailable_border_color',
		'molswc_notavailable_border_hover_color',
		'molswc_true_stock_level_admitted_in_filters',
		'molswc_delete_options_uninstall',
	);
	/// Then register each of them
	foreach ( $molswc_settings_array as $molswc_setting ) {
		register_setting( 'molswc-settings-group', $molswc_setting );
	}
}

// Delete all fragments stored as options if instructed so
if ( isset( $_GET['settings-updated'] ) ) { add_action( 'admin_notices', 'mofsb_fragments_purging_notice' ); }
function mofsb_fragments_purging_notice() {
	if ( get_option ( 'molswc_transient_keys_purging' ) == 1 )  {
		$delete_result = molswc_delete_fragments( 'molswc_cached_fragment' ); // 1. call the deleting function - based on partial name, including the prefix
		update_option( 'molswc_transient_keys_purging', '' ); // 2. set back the option to "unset"
		echo '<div class="notice-info notice" style="margin-left: 0px;"><p>Fragments delete finished, '.$delete_result.' fragments deleted.</p></div>';
	}
}

// This is the form in the admin page
function molswc_admin_options_page_callback() { ?>
    <h1>Lithe Shop Options Page</h1>
	<p>Adjust the options of the <strong>Litheskateboards Woocommerce customizations</strong> plugin, then click on any <strong>Save Changes</strong> button to apply the changes.</p>
	
	<p><strong>How "True Stock Levels" are calculated:</strong><br>
	<strong>In stock</strong> (3): stock is &gt; 0<br>
	<strong>Backorder</strong> (2): stock is &lt;= 0 AND &gt; (0 - backorder_level)<br>
	<strong>Preorder</strong> (1): otherwise<br>
	</p>

	<form method="post" action="options.php">
    <?php settings_fields( 'molswc-settings-group' ); ?>
    <?php do_settings_sections( 'molswc-settings-group' ); ?>

	<h2>Estimated delivery messages</h2>
	<p>Fill in the estimated delivery message, together with the details you need to display in Payment Plan buttons. Examples: "Max. 3 weeks" or "Usually 3 days" etc.<br>
	<strong>Leave empty to disable.</strong></p>
		
	<table class="form-table">

		<tr valign="top">
			<th scope="row">IN STOCK est. delivery: </th>
			<td> 
				<input name="molswc_estdelivery_instock" type="text" id="molswc_estdelivery_instock" aria-describedby="molswc_estdelivery_instock" value="<?php echo strip_tags(get_option( 'molswc_estdelivery_instock' )); ?>" class="regular-text">
				<span>(Free text allowed, but not HTML.)</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">BACK ORDER est. delivery: </th>
			<td> 
				<input name="molswc_estdelivery_backorder" type="text" id="molswc_estdelivery_backorder" aria-describedby="molswc_estdelivery_backorder" value="<?php echo strip_tags(get_option( 'molswc_estdelivery_backorder' )); ?>" class="regular-text">
				<span>(Free text allowed, but not HTML.)</span>
			</td>
		</tr>
				
		<tr valign="top">
			<th scope="row">PRE ORDER est. delivery: </th>
			<td> 
				<input name="molswc_estdelivery_preorder" type="text" id="molswc_estdelivery_preorder" aria-describedby="molswc_estdelivery_preorder" value="<?php echo strip_tags(get_option( 'molswc_estdelivery_preorder' )); ?>" class="regular-text">
				<span>(Free text allowed, but not HTML.)</span>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">PRE ORDER supplemental message: </th>
			<td> 
				<input name="molswc_pre_order_message" type="text" id="molswc_pre_order_message" aria-describedby="molswc_pre_order_message" value="<?php echo strip_tags(get_option( 'molswc_pre_order_message' )); ?>" class="regular-text">
				<span>(This is a supplemental message only for Pre Order. <strong>Leave empty to disable.</strong>)</span>
			</td>
		</tr>

	</table>
	
	<?php submit_button(); ?>

	<h2>Stock status labels</h2>
	<p>Fill in the stock hint labels that will show on Model/Width buttons in product page and pop up.</p>
		
	<table class="form-table">
		<tr valign="top">
			<th scope="row">IN STOCK label: </th>
			<td> 
				<input name="molswc_instock_label" type="text" id="molswc_instock_label" aria-describedby="molswc_instock_label" value="<?php echo strip_tags(get_option( 'molswc_instock_label' )); ?>" class="regular-text">
				<span>(Keep it (very!) short, or the label will exceed button width on some screen widths. Free text allowed, but not HTML.)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">BACK ORDER label: </th>
			<td> 
				<input name="molswc_backorder_label" type="text" id="molswc_backorder_label" aria-describedby="molswc_backorder_label" value="<?php echo strip_tags(get_option( 'molswc_backorder_label' )); ?>" class="regular-text">
				<span>(Keep it (very!) short, or the label will exceed button width on some screen widths. Free text allowed, but not HTML.)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">PRE ORDER label: </th>
			<td> 
				<input name="molswc_preorder_label" type="text" id="molswc_preorder_label" aria-describedby="molswc_preorder_label" value="<?php echo strip_tags(get_option( 'molswc_preorder_label' )); ?>" class="regular-text">
				<span>(Keep it (very!) short, or the label will exceed button width on some screen widths. Free text allowed, but not HTML.)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">NOT AVAILABLE label: </th>
			<td> 
				<input name="molswc_notavailable_label" type="text" id="molswc_notavailable_label" aria-describedby="molswc_notavailable_label" value="<?php echo strip_tags(get_option( 'molswc_notavailable_label' )); ?>" class="regular-text">
				<span>(Keep it (very!) short, or the label will exceed button width on some screen widths. Free text allowed, but not HTML.)</span>
			</td>
		</tr>
	</table>
	
	<?php submit_button(); ?>
	
		<h2>Buttons colors based on True Stock Status</h2>
	<p>Fill in the colors for the buttons. Use HEX value (like ) or "transparent". Things like "blue" or "green" may also work.</p>
		
	<table class="form-table">

		<tr valign="top">
			<th scope="row">IN STOCK button colors: </th>
			<td> 
				<span>Normal color:</span>
				<input name="molswc_instock_label_color" type="text" id="molswc_instock_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_label_color" value="<?php echo strip_tags(get_option( 'molswc_instock_label_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover color:</span>
				<input name="molswc_instock_label_hover_color" type="text" id="molswc_instock_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_label_hover_color" value="<?php echo strip_tags(get_option( 'molswc_instock_label_hover_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_instock_border_color" type="text" id="molswc_instock_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_border_color" value="<?php echo strip_tags(get_option( 'molswc_instock_border_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_instock_border_hover_color" type="text" id="molswc_instock_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_instock_border_hover_color" value="<?php echo strip_tags(get_option( 'molswc_instock_border_hover_color' )); ?>" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">BACK ORDER button colors: </th>
			<td> 
				<span>Normal color:</span>
				<input name="molswc_backorder_label_color" type="text" id="molswc_backorder_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_label_color" value="<?php echo strip_tags(get_option( 'molswc_backorder_label_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover color:</span>
				<input name="molswc_backorder_label_hover_color" type="text" id="molswc_backorder_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_label_hover_color" value="<?php echo strip_tags(get_option( 'molswc_backorder_label_hover_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_backorder_border_color" type="text" id="molswc_backorder_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_border_color" value="<?php echo strip_tags(get_option( 'molswc_backorder_border_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_backorder_border_hover_color" type="text" id="molswc_backorder_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_backorder_border_hover_color" value="<?php echo strip_tags(get_option( 'molswc_backorder_border_hover_color' )); ?>" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">PRE ORDER button colors: </th>
			<td> 
				<span>Normal color:</span>
				<input name="molswc_preorder_label_color" type="text" id="molswc_preorder_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_label_color" value="<?php echo strip_tags(get_option( 'molswc_preorder_label_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover color:</span>
				<input name="molswc_preorder_label_hover_color" type="text" id="molswc_preorder_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_label_hover_color" value="<?php echo strip_tags(get_option( 'molswc_preorder_label_hover_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_preorder_border_color" type="text" id="molswc_preorder_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_border_color" value="<?php echo strip_tags(get_option( 'molswc_preorder_border_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_preorder_border_hover_color" type="text" id="molswc_preorder_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_preorder_border_hover_color" value="<?php echo strip_tags(get_option( 'molswc_preorder_border_hover_color' )); ?>" class="regular-text">
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">N/A button colors: </th>
			<td> 
				<span>Normal color:</span>
				<input name="molswc_notavailable_label_color" type="text" id="molswc_notavailable_label_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_label_color" value="<?php echo strip_tags(get_option( 'molswc_notavailable_label_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover color:</span>
				<input name="molswc_notavailable_label_hover_color" type="text" id="molswc_notavailable_label_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_label_hover_color" value="<?php echo strip_tags(get_option( 'molswc_notavailable_label_hover_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Normal border color:</span>
				<input name="molswc_notavailable_border_color" type="text" id="molswc_notavailable_border_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_border_color" value="<?php echo strip_tags(get_option( 'molswc_notavailable_border_color' )); ?>" class="regular-text">
			</td>
			<td> 
				<span>Hover border color:</span>
				<input name="molswc_notavailable_border_hover_color" type="text" id="molswc_notavailable_border_hover_color" style="display: inline-block; width: auto;" aria-describedby="molswc_notavailable_border_hover_color" value="<?php echo strip_tags(get_option( 'molswc_notavailable_border_hover_color' )); ?>" class="regular-text">
			</td>
		</tr>

	</table>
	
	<?php submit_button(); ?>

	<h2>Designated options</h2>
	<p>Comma separated list of attributes used to create the columns in the product page and pop-up. Example: "Vert, Street".</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">The "designated options" are: </th>
			<td> 
				<input name="molswc_designated_options" type="text" id="molswc_designated_options" aria-describedby="molswc_designated_options" value="<?php echo strip_tags(get_option( 'molswc_designated_options' )); ?>" class="regular-text">
				<span>(Columns will be created with the order specified here.)</span>
			</td>
		</tr>
	</table>
	
	<h2>Excluded categories</h2>
	<p>Comma separated list of categories <strong>slugs</strong> that will be excluded from showing in SHOP page. Example: "accessories, wholesale".</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">The "excluded categories" are: </th>
			<td> 
				<input name="molswc_excluded_categories" type="text" id="molswc_excluded_categories" aria-describedby="molswc_excluded_categories" value="<?php echo strip_tags(get_option( 'molswc_excluded_categories' )); ?>" class="regular-text">
				<span>(Comma separated list of <strong>slugs</strong>, pick them from <a target="_blank" href="/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product">Categories page</a>.)</span>
			</td>
		</tr>
	</table>
	
	<h2>True Stock Level for boards filtering</h2>
	<p>This is the True Stock Level at which boards appear in Boards Filter on the main shop page if the selected options combination is available.</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Minimum True Stock Level is: </th>
			<td> 
				<select name="molswc_true_stock_level_admitted_in_filters" id="molswc_true_stock_level_admitted_in_filters" style="padding: 0 25px 2px 5px;">
					<option value="3" <?php if('3' == strip_tags(get_option( 'molswc_true_stock_level_admitted_in_filters' ))) { echo 'selected="selected"'; } ?>>Stock (3)</option>
					<option value="2" <?php if('2' == strip_tags(get_option( 'molswc_true_stock_level_admitted_in_filters' ))) { echo 'selected="selected"'; } ?>>Backorder (2)</option>
					<option value="1" <?php if('1' == strip_tags(get_option( 'molswc_true_stock_level_admitted_in_filters' ))) { echo 'selected="selected"'; } ?>>Preorder (1)</option>
				</select>
				<span>(All boards with the selected level <em>and levels above</em> will be shown for a particular selection of options)</span>
			</td>
		</tr>
	</table>
	
	<?php submit_button(); ?>
	
	<h2>Fragment cache options</h2>
	<p>Fragment cache caches bits of pages in database and return them when needed. For the moment it caches the product variations form, which is extremly costly to compute with all its variations, stock and prices.<br><strong>If there's a page cache plugin activated clear its cache after clearing this!</strong></p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Clear ALL fragments: </th>
			<td>
				<input name="molswc_transient_keys_purging" type="checkbox" value="1" <?php checked( '1', get_option( 'molswc_transient_keys_purging' ) ); ?> />
				<span>(Checking this box and clicking on Save button will delete all cached fragments.)</span>
			</td>
		</tr>
	</table>
	
	<h2>Enable Avia Debug</h2>
	<p>Checking the option below will enable Avia Debug, useful for copy page/product content from one website to another</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Enable Avia Debug: </th>
			<td>
				<input name="molswc_enable_avia_debug" type="checkbox" value="1" <?php checked( '1', get_option( 'molswc_enable_avia_debug' ) ); ?> />
				<span>(While this is enabled the Page Code text will be available at the end of Avia Page Builder section. Enable on other web site too to make copy/paste possible.)</span>
			</td>
		</tr>
	</table>
	
	<h2>Options management</h2>
	<p>Checking the option below will remove the Lithe Skateboards Customizations options from the database <em> when uninstalling the plugin</em>; else, they will stay there in case the plugin gets reinstalled later on.</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Delete this plugin options from database on uninstall: </th>
			<td>
				<input name="molswc_delete_options_uninstall" type="checkbox" value="1" <?php checked( '1', get_option( 'molswc_delete_options_uninstall' ) ); ?> />
				<span>(Otherwise they'll stay there in case the plugin gets reinstalled later - useful for updating)</span>
			</td>
		</tr>
	</table>
	
	<?php submit_button(); ?>
	
	<h2>Other relevant settings</h2>
	<p>These are settings that exists in Woocommerce but it would take more clicks to get to them - so we added links to them here, for convenience</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Global Attributes Edit Screen: </th>
			<td>
				<a href="/wp-admin/edit.php?post_type=product&page=product_attributes">Global attributes edit screen</a>
			</td>
		</tr>
	</table>

</form>
	
<?php } ?>
