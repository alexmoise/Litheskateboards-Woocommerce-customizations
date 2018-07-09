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
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
	jQuery('.xoo-qv-container .table.variations .tbody .value.td div.tax').attr('style', 'display:none;'); // pre-hide payment plans
	setTimeout(function() { jQuery('.scroll-hint').fadeOut('slow'); }, 5000);  // removing scroll hint icon after a while ...
	molswcDisableScroll(); // prevent body scrolling under the popup
	jQuery('.xoo-qv-main').bind('destroyed', function() { molswcEnableScroll(); filtercomplete = ''; }) // do stuff when popup closes, based on special event registered above ;-)
	jQuery(".each-attrib .value.td").children('.attrib:not(.has-been-disabled)').each(function(i) {
		jQuery(this).delay((Math.floor((Math.random()*1000)+1)) ).fadeTo( Math.floor((Math.random()*500)+1) ,1).delay( 100 );
	});
	setTimeout(function() { jQuery('.xoo-qv-container .attribute-model-and-size.tr').addClass('after-removed'); }, 1000);
	jQuery('.xoo-qv-container div.tax').click(function (e) { // execute the stuff below when clicking on a Payment Plan button
		if (!jQuery(this).hasClass( "has-been-disabled" ))
		{
			jQuery(this).find('input[type="radio"]').prop('checked', true);
			jQuery(".table.variations div.tax").off();
			jQuery(".table.variations div.tax *").off();
			jQuery(".table.variations div.tax").fadeTo("fast",0.2);
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
	jQuery( 'input[disabled="disabled"]'		).parent('div').toggleClass('has-been-disabled',true);
	jQuery( 'input:not([disabled="disabled"])'	).parent('div').toggleClass('has-been-disabled',false);
	jQuery( 'input[type="radio"]:checked'		).parent('div').toggleClass('radio-checked',true);
	jQuery( 'input[type="radio"]:checked'		).next().toggleClass('selected',true);
	jQuery( 'input[type="radio"]:not(:checked)'	).parent('div').toggleClass('radio-checked',false);
	jQuery( 'input[type="radio"]:not(:checked)'	).next().toggleClass('selected',false);
	if (jQuery(this).parents().find('input[type="radio"]').is(':checked')) // to hide or not hide payments if any other attribute is selected
	{
		jQuery('.attribute-pa_paying-plan').toggleClass('unhide-payments',true); // hide it with a hiding class
	} else {
		jQuery('.attribute-pa_paying-plan').toggleClass('unhide-payments',false); // reveal it by removing hiding class
	}
	jQuery( '*[class=""]' ).removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
	appendAttribPrices(); // Call the VARIATION PRICES Display function (see functions and variables defined below) ;-)
	molswcPaymentsButtonsReFit();
});

// === III. Product display functions -> SINGLE PRODUCT PAGE ONLY: ===
// Pre-hide payment plans in single product page
jQuery(document).ready(function() {
	jQuery('.single-product .table.variations .tbody .value.td div.tax').attr('style', 'display:none;');
});
// Add "has-been-disabled" class to initially disabled elements
jQuery(window).on('load', function() {
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
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
		jQuery(".table.variations div.tax").off();
		jQuery(".table.variations div.tax *").off();
		jQuery(".table.variations div.tax").fadeTo("fast",0.2);
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
	jQuery('.variations .select.attribute-pa_paying-plan .option').each(function () {
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
	// console.log('Prod ID: '+product_id);
	var data = {
		'action': 'wmp_variation_price_array',
		'product_id': product_id
	};
	// console.log('DATA: '+ JSON.stringify(data, null, 2) );
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
// Reset filters and bring in all boards again
jQuery(document).on('click', '#reset-product-filters', function() {
	jQuery('.product-filters select option').removeAttr('disabled'); 
	jQuery:document.getElementById('product-filters').reset();
	jQuery ('ul.products li').fadeIn();
});
// Take out not available boards
jQuery(document).delegate( '.product-filters', 'change', function() {
	var filtermodel = jQuery('.product-filters select[name="Models"] :selected').val();
	var filterwidth = jQuery('.product-filters select[name="Widths"] :selected').val();
	// if ( filtermodel ) { console.log(' Model: '+filtermodel); }
	// if ( filterwidth ) { console.log(' Width: '+filterwidth); }
	if ( filtermodel && filterwidth ) { 
		filtercomplete = filtermodel + ' ' + filterwidth;
		// console.log('101 COMPLETE: '+filtercomplete);
		jQuery ( 'ul.products li[data-custom-attribs-list*="'+filtercomplete+'"]').fadeIn(); 
		jQuery ( 'ul.products li' ).not('[data-custom-attribs-list*="'+filtercomplete+'"]').fadeOut(); 
	}
	// console.log('Combination: ' + filtermodel + ' ' + filterwidth + ' Boards have it: ' + checkIfModelWidthExists( filtermodel, filterwidth, getAllAttributes() ));
});
// Disable WIDTHS that are not possible in boards filters drop downs
jQuery(document).delegate( 'select[name="Models"]', 'change', function() {
	// console.log('Models changed!');
	var filtermodel = jQuery('.product-filters select[name="Models"] :selected').val();
	jQuery( 'select[name="Widths"] option' ).each( function( index, element ){
		var checkingwidth = jQuery( this ).val();
		var optionPossible = checkIfModelWidthExists( filtermodel, checkingwidth, getAllAttributes() );
		// console.log( 'Checking: '+filtermodel+' '+checkingwidth+' '+optionPossible );
		if(optionPossible == false) {
			jQuery( this ).attr('disabled', 'disabled');
		}
		if(optionPossible == true) {
			jQuery( this ).removeAttr('disabled');
		}
	});
});
// Disable MODELS that are not possible in boards filters drop downs
jQuery(document).delegate( 'select[name="Widths"]', 'change', function() {
	// console.log('Widths changed!');
	var filterwidth = jQuery('.product-filters select[name="Widths"] :selected').val();
	jQuery( 'select[name="Models"] option' ).each( function( index, element ){
		var checkingmodel = jQuery( this ).val();
		var optionPossible = checkIfModelWidthExists( checkingmodel, filterwidth, getAllAttributes() );
		// console.log( 'Checking: '+checkingmodel+' '+filterwidth+' '+optionPossible );
		if(optionPossible == false) {
			jQuery( this ).attr('disabled', 'disabled');
		}
		if(optionPossible == true) {
			jQuery( this ).removeAttr('disabled');
		}
	});
});
// FUNCTION to check if model-width combination is possible
function checkIfModelWidthExists (modeltocheck, widthtocheck, allcombinations) {
	// console.log(' InFunc: '+modeltocheck+' | '+widthtocheck+' | '+allcombinations);		
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

// === Payment Plans buttons re-fit into their container (used in popup AND in single board page)
// One refit function for all the cases:
function molswcPaymentsButtonsReFit() {
	if ( typeof resetHasBeenPressed !== 'undefined' && resetHasBeenPressed == 1 ) {
		jQuery('.table.variations .tbody .value.td div.tax').attr('style', 'display:none;')
		resetHasBeenPressed = 0; 
		console.log('resetHasBeenPressed: '+resetHasBeenPressed);
	}
	// fade out has-been-disabled
	jQuery('.table.variations .tbody .value.td div.tax.has-been-disabled').fadeOut(250);
	jQuery('.table.variations .tbody .value.td div.tax.has-been-disabled span.attrib-description').fadeOut(250);
	// count the not disabled ones:
	paymentsCnt = jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled)').length;
	setTimeout(function() { // then wait 250 and set the widths:
		jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled)').attr('style', 'width: calc((100% / '+paymentsCnt+') - 14px) !important;');
		// (how about on screen widths < 560px ??) 
		setTimeout(function() { // then wait another 250 and fade in what's not disabled:
			jQuery('#table-variations div.tax:not(.has-been-disabled)').fadeIn(250);
			jQuery('.table.variations .tbody .value.td div.tax:not(.has-been-disabled) span.attrib-description').fadeIn(250);
		}, 250);
	}, 250);
}
