/**
 * JS functions for Litheskateboards Woocommerce customizations plugin
 * Version: 1.4.22
 * (version above is equal with main plugin file version when this file was updated)
 */

// === Add and remove a class to the "Header" element when scrolling down or returning ===
// Used with 2 CSS rules to throw the logo over the top when scrolling - see the CSS file for that
var $header = jQuery( "header#header" );  
var $sticky = jQuery( ".sticky_column" );         
var appScroll = appScrollForward;
var appScrollPosition = 0;
var appScrollInterval = 70;
var upToScreenWidth = 520;
var appClassToAdd = "scrolled";
var scheduledAnimationFrame = false;
function appScrollReverse() {
	scheduledAnimationFrame = false;
	if ( appScrollPosition > appScrollInterval )
		return;
	$header.removeClass( appClassToAdd );
	//$sticky.removeClass( appClassToAdd );
	appScroll = appScrollForward;
}
function appScrollForward() {
	scheduledAnimationFrame = false;
	if ( appScrollPosition < appScrollInterval )
		return;
	$header.addClass( appClassToAdd );
	//$sticky.addClass( appClassToAdd );
	appScroll = appScrollReverse;
}
function appScrollHandler() {
	if ( window.screen.width > upToScreenWidth  )
		return;
	appScrollPosition = window.pageYOffset;
	if ( scheduledAnimationFrame )
		return;
	scheduledAnimationFrame = true;
	requestAnimationFrame( appScroll );
}
jQuery( window ).scroll( appScrollHandler );

// === Sticky section, pushing the main header out of the screen when a sticky_section touches that header
// (need to count these sticly sections later and only select first one ... or do something else with, or based on, the others)
// Use var appScrollInterval defined above so it doesn't push the header without being scrolled up first, otherwise it gets ... spooky on mobiles
jQuery(document).ready(function() {
	if(jQuery(".sticky_column").length > 0) {
		var target1 = document.querySelector('#header');
		var target2 = document.querySelector('.sticky_column');
		target1.classList.add('sticky_involved');
		target2.classList.add('sticky_involved');
		jQuery(window).scroll(function() {
			clearTimeout(jQuery.data(this, 'scrollTimer'));
			jQuery.data(this, 'scrollTimer', setTimeout(function() {
				// console.log('v10 '+molswc_distanceBetweenElems(target1,target2)+' Offs: '+window.pageYOffset+' Int: '+appScrollInterval);
				if (molswc_distanceBetweenElems(target1,target2)<0){
					if ( window.pageYOffset > appScrollInterval ) {
						target1.classList.add('sticky_touched');
						target2.classList.add('sticky_touched');
					}
				} else {
					target1.classList.remove('sticky_touched');
					target2.classList.remove('sticky_touched');
				}
			}, 100)); // trigger this at one 10th of second after user stops scrolling
		});
	};
});
// Function that calculates distance between bottom of first passed element and top of the second passed element. 
// Used above to calculate distance between header and a sticky section
function molswc_distanceBetweenElems(elem1, elem2) {
    var e1Rect = elem1.getBoundingClientRect();
    var e2Rect = elem2.getBoundingClientRect();
    var distance = e2Rect.top - e1Rect.bottom;
    return distance;
}

// === Add a line break in before Cards Icons in checkout; wait a bit for that form
jQuery(document).ready(function() { 
	setTimeout(function() { 
		jQuery('<br />').insertBefore('img.stripe-visa-icon.stripe-icon'); 
	}, 1500);
});

// ===  Now let's deal with product display functions  ===
// === I. Product display functions -> XOO POPUP ONLY: ===
// Initialize the special event needed for detecting XOO Popup removal
(function($){ $.event.special.destroyed = { remove: function(o) { if (o.handler) { o.handler() } } } })(jQuery)
// Define blank boards filters variable intentionally out of any scope, so we could use it in more functions later
jQuery(document).ready(function() { var filtercomplete; });
// Stuff to execute when XOO Popup open animation STARTS
jQuery( document ).on('animationstart', '.xoo-qv-inner-modal', function($) {
	ajaxAttribPrices(); // initialize the prices for the product loaded in popup (see functions and variables defined below)
});
// Stuff to execute when XOO Popup open animation ENDS
jQuery( document ).on('animationend', '.xoo-qv-inner-modal', function($) {
	jQuery("div.xoo-qv-summary > h1.product_title.entry-title").detach().prependTo("div.xoo-qv-main > div.product.type-product"); // move the title in DOM
	// jQuery("div.xoo-qv-plink").detach().appendTo("div.xoo-qv-main"); // also move Read More button
	jQuery( '.xoo-qv-inner-modal input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
	jQuery('.xoo-qv-container .table.variations .tbody .value.td div.tax').attr('style', 'display:none;'); // pre-hide payment plans
	setTimeout(function() { jQuery('.scroll-hint').fadeOut('slow'); }, 5000);  // removing scroll hint icon after a while ...
	molswcDisableScroll(); // prevent body scrolling under the popup
	jQuery('.xoo-qv-main').bind('destroyed', function() { molswcEnableScroll(); filtercomplete = ''; }) // do stuff when popup closes, based on special event registered above ;-)
	jQuery(".each-attrib .value-buttons.td").children('.attrib:not(.has-been-disabled)').each(function(i) {
		jQuery(this).fadeTo("fast",1);
	});
	setTimeout(function() { jQuery('.xoo-qv-container .table.variations > .tbody > .select.tr').addClass('after-removed'); }, 1000);
	jQuery('.xoo-qv-container div.tax').click(function (e) { // execute the stuff below when clicking on a Payment Plan button
		if (!jQuery(this).hasClass( "has-been-disabled" ))
		{
			jQuery(this).find('input[type="radio"]').prop('checked', true);
			jQuery(".table.variations div.tax:not(.has-been-disabled)").attr('style', 'cursor: not-allowed;');
			jQuery(".table.variations div.tax:not(.has-been-disabled)").off();
			jQuery(".table.variations div.tax:not(.has-been-disabled) *").off();
			jQuery(".table.variations div.tax:not(.has-been-disabled)").fadeTo("fast",0.2);
			jQuery(this).closest("form").submit();
		}
	});
	if ( typeof filtercomplete !== 'undefined' ) {
		setTimeout(function() { jQuery("label[value='"+filtercomplete+"']").trigger( "click" ); }, 1000); // automatically trigger the button corresponding to the filters selected before
	}
});
// .table.variations scrolling functions
jQuery(document).on('click', '.reset_variations', function(){ 
	if (jQuery(window).width() < 768) { jQuery('.xoo-qv-main').animate({scrollTop: '540px'}, 300); } // scroll back to product title when clicking on Reset Variations
	resetHasBeenPressed = 1;
});
jQuery(document).on('click', '.table.variations .attrib', function(){ 
	if (jQuery(window).width() < 768) { jQuery('.xoo-qv-main').animate({scrollTop: '2000px'}, 300); } // scroll down to payment options when clicking on any Variations
});

// === II. Product display functions -> BOTH POPUP AND SINGLE PRODUCT PAGE:
// .table.variations functions -> GENERAL
jQuery( document ).delegate( '.table.variations', 'change', function() {
	// Toggle classes of parent DIVs of radio buttons that ARE DISABLED or ARE SELECTED
	jQuery( '.table.variations input[disabled="disabled"]'			).parent('div').toggleClass('has-been-disabled',true);
	jQuery( '.table.variations input:not([disabled="disabled"])'	).parent('div').toggleClass('has-been-disabled',false);
	jQuery( '.table.variations input[type="radio"]:checked'			).parent('div').toggleClass('radio-checked',true);
	jQuery( '.table.variations input[type="radio"]:checked'			).next().toggleClass('selected',true);
	jQuery( '.table.variations input[type="radio"]:not(:checked)'	).parent('div').toggleClass('radio-checked',false);
	jQuery( '.table.variations input[type="radio"]:not(:checked)'	).next().toggleClass('selected',false);
	if (jQuery(this).parents().find('input[type="radio"]').is(':checked')) // to hide or not hide payments if any other attribute is selected
	{
		jQuery('.select.tax_attrib').toggleClass('unhide-payments',true); // hide it with a hiding class
	} else {
		jQuery('.select.tax_attrib').toggleClass('unhide-payments',false); // reveal it by removing hiding class
	}
	jQuery( '*[class=""]' ).removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
	jQuery( '*[style=""]' ).removeAttr('style'); // also remove "style" attribs when become empty
	
	// Hide Payment Plan description if only one plan remains
	if ( subs_user == 'no' ) {
		if ( jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled)').length == 1 )
		{
			jQuery(".table.variations .tax > .attrib-description").toggleClass('hide-because-is-single',true);
		} else {
			jQuery(".table.variations .tax > .attrib-description").toggleClass('hide-because-is-single',false);
		}
	}
	
	// Call the VARIATION PRICES Display function (see functions and variables defined below) ;-)
	setTimeout(function() { appendAttribPrices(); }, 500); // Maybe set a timeout here to give the time for Ajax to complete?
	molswcPaymentsButtonsReFit(); // Call the re-fit function to count the buttons and set their width according with their number
	
	// Now, about the Estimated Delivery time:
	jQuery('span.attribStockStatus').remove(); // First remove it from where it is displayed so it won't get displayed twice
	// ... then check if the currently "checked" button has "in_stock" status and if so, display "estdelivery_instock" variable defined for  in HTML
	if ( molswc_check_current_status() == 'var_stock_instock' && typeof estdelivery_instock !== 'undefined' ) { 
		jQuery('.tax > .attrib-description').before('<span class="attribStockStatus">'+estdelivery_instock+'</span>');
	}
	// ... then check if the currently "checked" button has "backorder" status and if so, display "estdelivery_backorder" variable defined in HTML
	if ( molswc_check_current_status() == 'var_stock_backorder' && typeof estdelivery_backorder !== 'undefined' ) { 
		jQuery('.tax > .attrib-description').before('<span class="attribStockStatus">'+estdelivery_backorder+'</span>');
	}
	
	// ... otherwise check if the currently "checked" button has "preorder" status and if so, display "estdelivery_preorder" variable defined in HTML
	if ( molswc_check_current_status() == 'var_stock_preorder' && typeof estdelivery_preorder !== 'undefined' ) { 
		jQuery('.tax > .attrib-description').before('<span class="attribStockStatus">'+estdelivery_preorder+'</span>');
	}
	
	// Finally add Pre Order status to Payment Plan direct purchase buttons
	if ( typeof pre_order_message !== 'undefined' ) {
		molswc_add_is_pre_order_info();
	}
});

// === III. Product display functions -> SINGLE PRODUCT PAGE ONLY: ===
// Pre-hide payment plans in single product page
jQuery(document).ready(function() {
	jQuery('.single-product .table.variations .tbody .value.td div.tax').attr('style', 'display:none;');
});
// Add "has-been-disabled" class to initially disabled elements
jQuery(window).on('load', function() {
	jQuery( '.table.variations input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
	// jQuery('html, body').animate({scrollTop: jQuery('#wrap_all').offset().top + 1}, 25); // scroll down a bit to hide the address bar, with no effect though...
});

// .table.variations scrolling functions
jQuery( document ).delegate( 'body #main .container .product .table.variations', 'change', function() {
	if (jQuery(this).parents().find('input[type="radio"]').is(':checked'))
	{
		if (jQuery(window).width() < 768) { jQuery('html,body').animate({scrollTop: jQuery(".unhide-payments").offset().top - 20}); } // also scroll down to payment options, now that these are un-hidden
	} 
});
jQuery('body #main .container .product .reset_variations').click(function(){
	if (jQuery(window).width() < 768) { jQuery('html,body').animate({scrollTop: jQuery(".product_title").offset().top - 20}); } // scroll back to product title when clicking on Reset Variations
});

// === Adjust boards thumbs brightness based on mouse position ===
jQuery(document).on('mouseenter', '.xoo-qv-button', function($) {
	jQuery(this).prev().toggleClass('brighter-board',true);
});
jQuery(document).on('mouseleave', '.xoo-qv-button', function($) {
	jQuery(this).prev().toggleClass('brighter-board',false);
});

// === Submit the form automatically (adding product to cart) when Payment Plan option is chosen ===
jQuery('body.single-product .table.variations div.tax').click(function (e) { // execute the stuff below when clicking on a Payment Plan button
	if (!jQuery(this).hasClass( "has-been-disabled" ))
	{
		jQuery(this).find('input[type="radio"]').prop('checked', true);
		jQuery(".table.variations div.tax:not(.has-been-disabled)").attr('style', 'cursor: not-allowed;');
		jQuery(".table.variations div.tax:not(.has-been-disabled)").off();
		jQuery(".table.variations div.tax:not(.has-been-disabled) *").off();
		jQuery(".table.variations div.tax:not(.has-been-disabled)").fadeTo("fast",0.2);
		jQuery(this).closest("form").submit();
	}
});

// === Under The XOO Popup scroll handling: ===
// Disable window scroll under popup 
function molswcDisableScroll() {
	var scrollPosition = [
	  self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
	  self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
	];
	var html = jQuery('html'); // it would make more sense to apply this to body, but IE7 won't have that
	html.data('scroll-position', scrollPosition);
	html.data('previous-overflow', html.css('overflow'));
	html.css('overflow', 'hidden');
	window.scrollTo(scrollPosition[0], scrollPosition[1]);
}
// Enable window scroll under popup
function molswcEnableScroll() {
	var html = jQuery('html');
	var scrollPosition = html.data('scroll-position');
	html.css('overflow', html.data('previous-overflow'));
	window.scrollTo(scrollPosition[0], scrollPosition[1])
}

// === Data and functions definitions for the Prices of Paying Plans: ===
// Initial VAR definition
if (typeof(wm_pvar) === 'undefined') {
    var wm_pvar = {
        products_by_attribute_ids: [],
        products_prices: [],
        products_attributes: [],
        products_attributes_values: [],
        additional_cost_indicator: '+',
        hide_price_when_zero: "true",
        format_string: "{1} ({3}{0} {2})",
        format_string_from: "{1} (ab {0} {2})",
        lowest_price: 0,
        display_style: '0',
        show_from_price: "false",
        product_id: 0
    };
}
// Get the current attributes IDs as key pairs (like "16,32"):
function fcurrSelectedKey() { 
	jQuery('.variations .select .option.selected').each(function () {
		localSelectedVal = jQuery(this).attr('value');
		for (var key in wm_pvar.products_attributes_values) { 
			if (wm_pvar.products_attributes_values[key] === localSelectedVal) {
				var currSelectedKey = key; 
				result = currSelectedKey;
			}
		}
	});
	return result;
}
// Get the Paying Plan "value" (it's actually the slug), and create an array object together with the corresponding attributes IDs:
function fcurrPayingplanKey() {
	var currKey = [];
	jQuery('.variations .select.tax_attrib .option').each(function () {
		localVal = jQuery(this).attr('value'); 
		for (var key in wm_pvar.products_attributes_values) { 
			if (wm_pvar.products_attributes_values[key] === localVal) {
				tempKey = fcurrSelectedKey()+','+key;
				currKey[tempKey] = localVal; 
				result = currKey;
			}
		}
	});
	jsnCurrKey = Object.assign({}, result);
	return jsnCurrKey;
}
// Get the additional Product Attribute that corresponds to a attributes IDs pair and pull the price based on that; then pair the Paying Plan "value" with price, yey!
function fselectedPlanAttribIDs() {
	var attribData = [];
	selectedPlans = fcurrPayingplanKey(); 
	for (var selKey in selectedPlans) { 
		var selPlan = selectedPlans[selKey]; 
		for (var akey in wm_pvar.products_by_attribute_ids) { 
			if (akey === selKey) {
				var selAttrID = wm_pvar.products_by_attribute_ids[akey]; 
				for (var pkey in wm_pvar.products_prices) { 
					if (pkey == selAttrID) {
						var selPrice = wm_pvar.products_prices[pkey]; 
						attribData[selPlan] = selPrice; 
						result = attribData; 
					}
				}
			}
		}
	}
	jsnAttribData = Object.assign({}, result);
	return jsnAttribData;
}
// Now get the slug & price pairs, look for the slug and add the price in a <span> after the Payment Plan with the slug as value attribute
function appendAttribPrices() { 
	jQuery('span.attribPrice').remove();
	attribPrices = fselectedPlanAttribIDs();
	for (var priceOf in attribPrices) {
		var priceAmount = attribPrices[priceOf];
		jQuery('label[value="'+priceOf+'"]').after('<span class="attribPrice">$'+priceAmount+'</span>');
	}
}

// === Functions to initialize prices for the product loaded in XOO popup
// Get data values with an ajax call
function ajaxAttribPrices() {
	var $cart = jQuery(".xoo-qv-main form.variations_form");
	if (typeof $cart === 'undefined' || $cart.length <= 0) { return; }
	var product_id = $cart.attr('data-product_id'); 
	var data = {
		'action': 'wmp_variation_price_array',
		'product_id': product_id
	};
	jQuery.ajax({
		url: wm_pvar.ajax_url,
		type: "POST",
		data: data})
	.always(function (data) {
		data = data || [];
		wm_pvar.lowest_price = data['lowest_price'] || wm_pvar.lowest_price;
		wm_pvar.products_attributes = data['products_attributes'] || wm_pvar.products_attributes;
		wm_pvar.products_attributes_values = data['products_attributes_values'] || wm_pvar.products_attributes_values;
		wm_pvar.products_by_attribute_ids = data['products_by_attribute_ids'] || wm_pvar.products_by_attribute_ids;
		wm_pvar.products_prices = data['products_prices'] || wm_pvar.products_prices;
		wm_pvar.product_id = jQuery("form.variations_form").attr('data-product_id');
		initAttribVariables();
	});
}
// Initialize data values we got with the ajaxAttribPrices() function above
function initAttribVariables() {
        wm_pvar.hide_price_when_zero = (wm_pvar_settings.hide_price_when_zero === true || wm_pvar_settings.hide_price_when_zero === "true");
        wm_pvar.show_from_price = (wm_pvar_settings.show_from_price === true || wm_pvar_settings.show_from_price === "true");
        wm_pvar.display_style = parseInt(wm_pvar_settings.display_style || 0);
        wm_pvar.lowest_price = parseFloat(wm_pvar_settings.lowest_price || 0);
        wm_pvar.product_id = parseInt(wm_pvar_settings.product_id || 0);
        wm_pvar.num_decimals = parseInt(wm_pvar_settings.num_decimals || 2);
        wm_pvar.decimal_sep = wm_pvar_settings.decimal_sep || ",";
        wm_pvar.thousands_sep = wm_pvar_settings.thousands_sep || "";
        wm_pvar.format_string = wm_pvar_settings.format_string || "{1} ({3}{0} {2})";
        wm_pvar.format_string_from = wm_pvar_settings.format_string_from || "{1} (ab {0} {2})";
        wm_pvar.currency = wm_pvar_settings.currency || "$";
        wm_pvar.additional_cost_indicator = wm_pvar_settings.additional_cost_indicator || '+';
        wm_pvar.ajax_url = wm_pvar_settings.ajax_url || '/wp-admin/admin-ajax.php';
}

// === Boards filtering functions
// *** Function callers below:
jQuery(document).ready(function() { rackFiltersInit(); }); // Run some functions to show the boards rack prepared at shop display
jQuery(document).on( 'click',  '#reset-product-filters', function() { rackFiltersReset(); }); // Reset filters and bring in all boards again
jQuery(document).on( 'click',  '.not_products a.woocommerce-LoopProduct-link', function() { rackFiltersReset(); }); // Reset filters and bring in all boards again - when clicking the NoBoards placeholder as well
jQuery(document).on( 'change', '.product-filters', function() { takeOutUnavailableBoards(); showHideNoBoardsPlaceholder(); }); // Fire the (un)available boards management functions
jQuery(document).on( 'change', 'select[name="Models"]', function() { disableImpossibleWitdhs(); }); // Disable WIDTHS that are not possible at any MODEL change
jQuery(document).on( 'change', 'select[name="Widths"]', function() { disableImpossibleModels(); }); // Disable MODELS that are not possible at any WIDTH change
// *** Functions definitions below:
// FUNCTIONS collection to initialize rack filters at 1st display
function rackFiltersInit() {
	populateBoardFilters(); // Populate the filters drop-downs with options to match available boards in the page
	URLParametersPreSelectFilters(); // Then add Select attributes if we have GET parameters for this
	// enableOnlyAvailableModelsAndWidths(); // Then *enable* only available Models and Widths (they come out initially "disabled")
	takeOutUnavailableBoards(); // Then take out unavailable boards - in case model/width comes preselected via GET variables
	disableImpossibleWitdhs(); // Then arrange the filters to match availability of board combinations - again in case model/width comes preselected via GET variables
	disableImpossibleModels();
}
// FUNCTION to reset rack filters
function rackFiltersReset() {
	jQuery( 'select[name="Models"] > option' ).removeAttr('selected', 'disabled', 'hidden'); // remove all attributes from Models
	jQuery( 'select[name="Widths"] > option' ).removeAttr('selected', 'disabled', 'hidden'); // remove all attributes from Widths
	jQuery:document.getElementById('product-filters').reset(); // then reset the 'product-filters'
	jQuery('.product-filters select option').attr('disabled', 'disabled'); // disable all drop down options ...
	enableOnlyAvailableModelsAndWidths(); // ...then enable back only those that are available!
	jQuery ('div.real_products ul.products li').fadeIn(200); // ...and fade in all boards again!
	jQuery("#product-filters select").find('option:contains("All")').attr("selected",true); // ... and select the "All.." options once again
	showHideNoBoardsPlaceholder(); // ... and finally deal with Unavailable Placeholder if needed
}
// FUNCTION to take out of "the rack" all not available boards
function takeOutUnavailableBoards() {
	var filtermodel = jQuery('.product-filters select[name="Models"] :selected').val();
	var filterwidth = jQuery('.product-filters select[name="Widths"] :selected').val();
	if ( filtermodel ) { 
		jQuery ( 'div.real_products ul.products li[data-custom-attribs-list*="'+filtermodel+'"]').fadeIn(150); 
		jQuery ( 'div.real_products ul.products li' ).not('[data-custom-attribs-list*="'+filtermodel+'"]').fadeOut(150); 
	}
	if ( filterwidth ) { 
		jQuery ( 'div.real_products ul.products li[data-custom-attribs-list*="'+filterwidth+'"]').fadeIn(150); 
		jQuery ( 'div.real_products ul.products li' ).not('[data-custom-attribs-list*="'+filterwidth+'"]').fadeOut(150); 
	}
	if ( filtermodel && filterwidth ) { 
		filtercomplete = filtermodel + ' ' + filterwidth;
		jQuery ( 'div.real_products ul.products li[data-custom-attribs-list*="'+filtercomplete+'"]').fadeIn(150); 
		jQuery ( 'div.real_products ul.products li' ).not('[data-custom-attribs-list*="'+filtercomplete+'"]').fadeOut(150); 
	}
}
// FUNCTION to show/hide "boards unavailable" board placeholder based on number of dispayed available boards
function showHideNoBoardsPlaceholder() { 
	setTimeout(function() {
		jQuery( ".lithe_rack_type_display" ).each(function() {
		  var boards_hidden = jQuery( this ).find('.real_products ul.products > li.product:hidden').length;
		  var boards_showed = jQuery( this ).find('.real_products ul.products > li.product').length;
		  if ( boards_showed == boards_hidden ) {
				jQuery( this ).find('.not_products').show();
				jQuery( this ).find('.rack_top').hide();
				jQuery( this ).find('.rack_bottom').hide();
		  } else {
				jQuery( this ).find('.not_products').hide();
				jQuery( this ).find('.rack_top').show();
				jQuery( this ).find('.rack_bottom').show();
		  }
		});
	}, 310); // 310 is set to be bigger than the 150 x 2 set in fadeIn and fadeOut ops in the takeOutUnavailableBoards() function
}
// FUNCTION to disable WIDTHS that are not possible in boards filters drop downs
function disableImpossibleWitdhs() {
	var filtermodel = jQuery('.product-filters select[name="Models"] :selected').val();
	jQuery( 'select[name="Widths"] > option' ).each( function( index, element ){
		var checkingwidth = jQuery( this ).val();
		var optionPossible = checkIfModelWidthExists( filtermodel, checkingwidth, getAllAttributes() );
		if(optionPossible == false) {
			jQuery( this ).attr('disabled', 'disabled');
		}
		if(optionPossible == true) {
			jQuery( this ).removeAttr('disabled');
		}
	});
}
// FUNCTION to disable MODELS that are not possible in boards filters drop downs
function disableImpossibleModels() {
	var filterwidth = jQuery('.product-filters select[name="Widths"] :selected').val();
	jQuery( 'select[name="Models"] > option' ).each( function( index, element ){
		var checkingmodel = jQuery( this ).val();
		var optionPossible = checkIfModelWidthExists( checkingmodel, filterwidth, getAllAttributes() );
		if(optionPossible == false) {
			jQuery( this ).attr('disabled', 'disabled');
		}
		if(optionPossible == true) {
			jQuery( this ).removeAttr('disabled');
		}
	});
}
// FUNCTION to loop through all Models and Widths IN SELECTORS and enable only available ones
function enableOnlyAvailableModelsAndWidths() {
	// Loop through all Widths and disable those not existing in *any* data-custom-attribs-list
	jQuery(".product-filters select[name='Widths'] > option:not([hidden])").each(function() {
		var widthtocheck = this.value;
		jQuery(".product-filters select[name='Models'] > option:not([hidden])").each(function() {
			var modeltocheck = this.value;
			var optionPossible = checkIfModelWidthExists( modeltocheck, widthtocheck, getAllAttributes() );
			if(optionPossible == true) {
				jQuery( this ).removeAttr('disabled');
			}
		});
	});
	// Loop through all Models and disable those not existing in *any* data-custom-attribs-list
	jQuery(".product-filters select[name='Models'] > option:not([hidden])").each(function() {
		var modeltocheck = this.value;
		jQuery(".product-filters select[name='Widths'] > option:not([hidden])").each(function() {
			var widthtocheck = this.value;
			var optionPossible = checkIfModelWidthExists( modeltocheck, widthtocheck, getAllAttributes() );
			if(optionPossible == true) {
				jQuery( this ).removeAttr('disabled');
			}
		});
	});
}
// FUNCTION to check if model-width combination is possible
function checkIfModelWidthExists (modeltocheck, widthtocheck, allcombinations) {
	var hasPartialMatch = allcombinations.some(function(v){ return v.indexOf(modeltocheck+' '+widthtocheck)>=0 }) // check array elements by partial strings
	return hasPartialMatch;
}
// FUNCTION to get all unique board attributes 
function getAllAttributes() { 
	var allattributes = [];	
	var liattributesfulllist = [];
	jQuery.each(jQuery('li[data-custom-attribs-list]'), function() { 
		var liattributes = (jQuery(this).attr('data-custom-attribs-list'));
		var liattributesarray = liattributes.split(',');
		for (var liakey in liattributesarray) {
			var liattributesfulllist = liattributesarray[liakey];
			if (jQuery.inArray( liattributesfulllist, allattributes ) == -1) {
				allattributes.push(liattributesfulllist);
			}
		}
	}); 
	return allattributes;
}
// FUNCTION to populate the filters initially
function populateBoardFilters() { 
	var allModelsUnique = [];
	var allWidthsUnique = [];
	// Get all possible attributes first
	var allAttribs = getAllAttributes();
	// Loop through all attributes we got before
	jQuery.each(allAttribs, function(attID,attComplete) {
		// Split the attribute at space, so we get Model and Width (What happens if there will be a Model with 2 words? Need to catch that later)
		var attSplit = attComplete.split(' ');
		// If not there already, add Model and Width to their dedicated arrays
		if (jQuery.inArray( attSplit[0], allModelsUnique ) == -1) { allModelsUnique.push(attSplit[0]); }
		if (jQuery.inArray( attSplit[1], allWidthsUnique ) == -1) { allWidthsUnique.push(attSplit[1]); }
	});
	// Sort the attributes arrays first
	allModelsUnique.sort(); allWidthsUnique.sort();
	// Finally add the options with names and values based on the arrays above
	jQuery.each(allModelsUnique, function(modelID,modelName) { jQuery("#filterModels").append(new Option(modelName, modelName)); });
	jQuery.each(allWidthsUnique, function(widthID,widthName) { jQuery("#filterWidths").append(new Option(widthName, widthName)); });	
};

// FUNCTION to get URL parameters, and pre-set the filters afterwards
function URLParametersPreSelectFilters(){
	// Read the URL first
    var urlString = window.location.href;
	var url = new URL(urlString);
	// Get the "model" and "width" parameters from URL
	var urlModel = url.searchParams.get("model");
	var urlWidth = url.searchParams.get("width");
	// Check if pre-selected option would be possible to select
	var optionPossible = checkIfModelWidthExists( urlModel, urlWidth, getAllAttributes() );
	//Then, based on the result of the above:
	if (optionPossible) {
		// Add the "select" properties to these options if present & defined
		if (urlModel !== null) { jQuery('#filterModels option[value="'+urlModel+'"]').prop("selected","selected"); }
		if (urlWidth !== null) { jQuery('#filterWidths option[value="'+urlWidth+'"]').prop("selected","selected"); }
	} else { 
		// Do something else (TBD what):
		console.log('Pre-selected options are not possible, non-existent or invalid'); 
	}
	showHideNoBoardsPlaceholder();
};

// === Payment Plans buttons re-fit into their container (used in popup AND in single board page)
// One refit function for all the cases:
function molswcPaymentsButtonsReFit() {
	var paymentsCnt;
	if ( typeof resetHasBeenPressed !== 'undefined' && resetHasBeenPressed == 1 ) {
		jQuery('.table.variations .tbody .value.td div.tax').attr('style', 'display:none;')
		resetHasBeenPressed = 0;
	}
	// fade out has-been-disabled
	jQuery('.table.variations .tbody .value.td div.tax.has-been-disabled').fadeOut(250);
	jQuery('.table.variations .tbody .value.td div.tax.has-been-disabled span.attrib-description').fadeOut(250);
	// count the not disabled ones and set variable depending on screen width:
	// var windwindth = jQuery(window).width(); console.log('WindWidth= '+windwindth);
	if (jQuery(window).width() < 560) {
		paymentsCnt = '1';
	} else {
		paymentsCnt = jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled)').length;
	}
	setTimeout(function() { // then wait 250 and set the widths:
		// jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled)').attr('style', 'width: calc((100% / '+paymentsCnt+') - 14px) !important;'); 
		setTimeout(function() { // then wait another 250 and fade in what's not disabled:
			jQuery('#table-variations div.tax:not(.has-been-disabled)').fadeIn(250);
			jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled) span.attrib-description:not(.hide-because-is-single)').fadeIn(250);
		}, 250);
	}, 250);
}

// === Check stock status of currently selected Width button
// Get the stock status from the "data-stock-status" attribute that is outputted in the form by the PHP function responsible with the buttons (called "print_attribute_radio_attrib")
function molswc_check_current_status() {
	var stockTarget = jQuery('div.attrib input[type="radio"]:checked');
	var stockStatus = jQuery(stockTarget).attr("data-stock-status");
	return stockStatus;
}

// === Add Pre Order information in Payment Plan purchase buttons
// Function to return the "Model-Width" selected by clicking a button
function molswc_get_selected_model_width() {
	var selected_label = jQuery('div.attrib label.attrib.option.selected');
	var selected_model_width = jQuery(selected_label).attr("value");
	return selected_model_width;
}

// Function that add the <span> and Pre Order text
function molswc_add_is_pre_order_info() {
	jQuery('span.attribPreorderStatus').remove();
	jQuery.each(jQuery('.variations_form').data("product_variations"), function() {
		var selected_model_width = molswc_get_selected_model_width();
		var all_model_width = this.attributes['attribute_model-width'];
		if ( this.attributes['attribute_model-width'] == selected_model_width ) {
			var curr_pre_order = this.is_pre_order;
			if (curr_pre_order == 'yes') {
				var curr_paying_plan = this.attributes["attribute_pa_paying-plan"];
				jQuery('div.tax[data-text-name="'+curr_paying_plan+'"] .attribStockStatus').before('<span class="attribPreorderStatus">'+pre_order_message+'</span>');
			}
		}
	});
}
