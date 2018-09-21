<?php
/**
 * Settings Page for Litheskateboards Woocommerce customizations
 * Version: 1.0.21
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
	register_setting( 'molswc-settings-group', 'molswc_estdelivery_instock' );
	register_setting( 'molswc-settings-group', 'molswc_estdelivery_backorder' );
	register_setting( 'molswc-settings-group', 'molswc_designated_options' );
	register_setting( 'molswc-settings-group', 'molswc_excluded_categories' );
	register_setting( 'molswc-settings-group', 'molswc_fragment_cache_buildid' );
	register_setting( 'molswc-settings-group', 'molswc_enable_avia_debug' );
	register_setting( 'molswc-settings-group', 'molswc_delete_options_uninstall' );
}

// This is the form in the admin page
function molswc_admin_options_page_callback() { ?>
    <h1>Lithe Shop Options Page</h1>
	<p>Adjust the options of the <strong>Litheskateboards Woocommerce customizations</strong> plugin.</p>

	<form method="post" action="options.php">
    <?php settings_fields( 'molswc-settings-group' ); ?>
    <?php do_settings_sections( 'molswc-settings-group' ); ?>
	
	<?php submit_button(); ?>

	<h2>Estimated delivery time</h2>
	<p>Fill in the estimated delivery time, together with the details you need to display in Payment Plan buttons. Examples: "Max. 3 weeks" or "Usually 3 days" etc.<br>
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
			<th scope="row">BACKORDER est. delivery: </th>
			<td> 
				<input name="molswc_estdelivery_backorder" type="text" id="molswc_estdelivery_backorder" aria-describedby="molswc_estdelivery_backorder" value="<?php echo strip_tags(get_option( 'molswc_estdelivery_backorder' )); ?>" class="regular-text">
				<span>(Free text allowed, but not HTML.)</span>
			</td>
		</tr>
	</table>
	
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
	
	<h2>Fragment cache options</h2>
	<p>Fragment cache caches bits of pages in database and return them when needed. For the moment it caches the product variations form, which is extremly costly to compute with all its variations, stock and prices.<br><strong>If there's a page cache plugin activated clear its cache after clearing this!</strong></p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Fragment Cache build ID: </th>
			<td> 
				<input name="molswc_fragment_cache_buildid" type="number" min="1" max="9999" step="1" id="molswc_fragment_cache_buildid" aria-describedby="molswc_fragment_cache_buildid" value="<?php echo strip_tags(get_option( 'molswc_fragment_cache_buildid' )); ?>" class="regular-text">
				<span>(Just changing this number (must be a number!) all the fragments will be invalid - and a new set will be generated at next visit/crawl)</span>
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
				<span>(While this is enabled the Page Code text are will be available at the end of Avia Page Builder section. Enable on other web site too to make copy/paste possible.)</span>
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
