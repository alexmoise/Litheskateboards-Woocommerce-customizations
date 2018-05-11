// === Toggle a class of parent DIV of disabled elements at first window.load anyway
jQuery(window).on('load', function() {
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
});
// === .table.variations functions -> GENERAL
jQuery( document ).delegate( '.table.variations', 'change', function() {
	//jQuery(this).unbind('click');
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
	
});
// === .table.variations scrolling functions -> NON-POPUP, PRODUCT PAGE ONLY
jQuery( document ).delegate( 'body #main .container .product .table.variations', 'change', function() {
	//jQuery(this).unbind('click');
	if (jQuery(this).parents().find('input[type="radio"]').is(':checked'))
	{
		if (jQuery(window).width() < 768) { jQuery('html,body').animate({scrollTop: jQuery(".unhide-payments").offset().top - 20}); } // also scroll down to payment options, now that these are un-hidden
	} 
});
jQuery('body #main .container .product .reset_variations').click(function(){
	if (jQuery(window).width() < 768) { jQuery('html,body').animate({scrollTop: jQuery(".product_title").offset().top - 20}); } // scroll back to product title when clicking on Reset Variations
});

// === .table.variations scrolling functions -> POPUP ONLY
jQuery(document).on('click', '.xoo-qv-main .reset_variations', function(){ 
	if (jQuery(window).width() < 768) { jQuery('.xoo-qv-main').animate({scrollTop: '0px'}, 300); } // scroll back to product title when clicking on Reset Variations
});
jQuery(document).on('click', '.xoo-qv-main .table.variations .attrib', function(){ 
	var elem = jQuery('.xoo-qv-main');
	if (jQuery(window).width() < 768) { jQuery('.xoo-qv-main').animate({scrollTop: elem.height()}, 300); } // scroll down to payment options when clicking on any Variations
});

// === Submit the form automatically (adding product to cart) when Payment Plan option is chosen
jQuery( document ).delegate( '.table.variations input[name="attribute_pa_paying-plan"]', 'click', function(event) {
	//jQuery(this).unbind('click');
	jQuery(this).closest("form").submit();
});

// === Data and functions definitions for the Prices of Paying Plans:
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
