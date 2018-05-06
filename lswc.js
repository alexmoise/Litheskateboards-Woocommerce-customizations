// === Toggle a class of parent DIV of disabled elements at first window.load anyway
jQuery(window).on('load', function() {
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
});
// === Toggle a class of parent DIV of radio buttons that ARE DISABLED or ARE SELECTED - these are styled further in CSS
jQuery( document ).delegate( '.table.variations', 'change', function(event) {
	jQuery(this).unbind('click');
	jQuery( 'input[disabled="disabled"]'			).parent('div').toggleClass('has-been-disabled',true);
	jQuery( 'input:not([disabled="disabled"])'	).parent('div').toggleClass('has-been-disabled',false);
	
	jQuery( 'input[type="radio"]:checked'		).parent('div').toggleClass('radio-checked',true);
	jQuery( 'input[type="radio"]:checked'		).next().toggleClass('selected',true);
	
	jQuery( 'input[type="radio"]:not(:checked)'	).parent('div').toggleClass('radio-checked',false);
	jQuery( 'input[type="radio"]:not(:checked)'	).next().toggleClass('selected',false);
	
	if (jQuery(this).parents().find('input[type="radio"]').is(':checked')) // "payment plan" is hidden by default (in CSS), here add class to unhide it if any other attribute is selected
	{
		jQuery('.attribute-pa_paying-plan').toggleClass('unhide-payments',true);
		if (jQuery(window).width() < 768) {
			jQuery('html,body').animate({scrollTop: jQuery(".unhide-payments").offset().top - 20}); // here scroll down to payment options, now that these are un-hidden
		}
	} else {
		jQuery('.attribute-pa_paying-plan').toggleClass('unhide-payments',false);
	}
	jQuery( '*[class=""]' ).removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
	
	appendAttribPrices(); // Call the VARIATION PRICES Display function (see functions and variables defined below) ;-)
	
});
// === Scroll back up on Clear Selection
jQuery('.reset_variations').click(function(){
	if (jQuery(window).width() < 768) {
		jQuery('html,body').animate({scrollTop: jQuery(".product_title").offset().top - 20});
	}
});
// === Submit the form automatically (adding product to cart) when Payment Plan option is chosen
jQuery( document ).delegate( '.table.variations input[name="attribute_pa_paying-plan"]', 'click', function(event) {
	jQuery(this).unbind('click');
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
