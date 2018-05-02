// === Toggle a class of parent DIV of disabled elements at first window.load anyway
jQuery(window).on('load', function() {
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
});
// === Toggle a class of parent DIV of radio buttons that ARE DISABLED or ARE SELECTED - these are styled further in CSS
jQuery( document ).delegate( '.table.variations', 'change', function(event) {
	$(this).unbind('click');
	$( 'input[disabled="disabled"]'			).parent('div').toggleClass('has-been-disabled',true);
	$( 'input:not([disabled="disabled"])'	).parent('div').toggleClass('has-been-disabled',false);
	
	$( 'input[type="radio"]:checked'		).parent('div').toggleClass('radio-checked',true);
	$( 'input[type="radio"]:checked'		).next().toggleClass('selected',true);
	
	$( 'input[type="radio"]:not(:checked)'	).parent('div').toggleClass('radio-checked',false);
	$( 'input[type="radio"]:not(:checked)'	).next().toggleClass('selected',false);
	
	if ($(this).parents().find('input[type="radio"]').is(':checked')) // "payment plan" is hidden by default (in CSS), here add class to unhide it if any other attribute is selected
	{
		$('.attribute-pa_paying-plan').toggleClass('unhide-payments',true);
		if ($(window).width() < 768) {
			$('html,body').animate({scrollTop: $(".unhide-payments").offset().top - 20}); // here scroll down to payment options, now that these are un-hidden
		}
	} else {
		$('.attribute-pa_paying-plan').toggleClass('unhide-payments',false);
	}
	$( '*[class=""]' ).removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
});
// === Scroll back up on Clear Selection
jQuery('.reset_variations').click(function(){
	if ($(window).width() < 768) {
		$('html,body').animate({scrollTop: $(".product_title").offset().top - 20});
	}
});
// === Submit the form automatically (adding product to cart) when Payment Plan option is chosen
jQuery( document ).delegate( '.table.variations input[name="attribute_pa_paying-plan"]', 'click', function(event) {
	$(this).unbind('click');
	$(this).closest("form").submit();
});

// === Start getting the variation prices
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

//jQuery(document).ready(function ($) { "use strict"; console.log('wm_pvar: '+ JSON.stringify(wm_pvar, null, 2) ); /* ======================================================================== wm_pvar */ });

jQuery( document ).delegate( '.table.variations', 'change', function(event) { $(this).unbind('click'); console.log( "Variations changed! ====================" ); // ======================= Variations changed :-)
	
	function fcurrSelectedKey() {
		$('.variations .select .option.selected').each(function () {
			localSelectedVal = $(this).attr('value');
			for (var key in wm_pvar.products_attributes_values) { //console.log ('INC-localSelectedVal-key: '+key); // ======================================================================= INC-localSelectedVal-key
				if (wm_pvar.products_attributes_values[key] === localSelectedVal) {
					var currSelectedKey = key; //console.log ('currSelectedKey inF: '+currSelectedKey ); // ================================================================================== currSelectedKey
					result = currSelectedKey;
				}
			}
		});
		return result;
	}
	
	function fcurrPayingplanKey() {
		var currKey = [];
		$('.variations .select.attribute-pa_paying-plan .option').each(function () {
			localVal = $(this).attr('value');
			for (var key in wm_pvar.products_attributes_values) { //console.log ('INC-localVal: '+localVal); // ============================================================================== INC-localVal-key
				if (wm_pvar.products_attributes_values[key] === localVal) {
					tempKey = fcurrSelectedKey()+','+key;
					currKey[tempKey] = localVal; //console.log ('currKey inF: '+currKey ); // ================================================================================================ currKey
					result = currKey;
				}
			}
		});
		jsnCurrKey = Object.assign({}, result);
		return jsnCurrKey;
	}
	
	function fselectedPlanAttribIDs() {
		selectedPlans = fcurrPayingplanKey(); // console.log('selectedPlans: '+ JSON.stringify(selectedPlans, null, 2) ); // =============================================================== selectedPlans
		for (var selKey in selectedPlans) { console.log('selKey: '+selKey ); // ============================================================================================================ selKey
			var selPlan = selectedPlans[selKey]; console.log('selPlan: '+selPlan ); // ===================================================================================================== selPlan
			for (var akey in wm_pvar.products_by_attribute_ids) { //console.log('akey: '+akey ); // ========================================================================================== key
				if (akey === selKey) {
					var selAttrID = wm_pvar.products_by_attribute_ids[akey]; console.log('selAttrID: '+selAttrID ); // ===================================================================== selAttrID
					
					for (var pkey in wm_pvar.products_prices) { //console.log('pkey: '+pkey ); // ========================================================================================== pkey
						if (pkey == selAttrID) {
							var selPrice = wm_pvar.products_prices[pkey]; console.log('selPrice: '+selPrice+' ##########' ); // =========================================================== selPrice
						}
					}
					
					result = selAttrID; // not good right now, but who cares (at this moment)? :-D
				}
			}
		}
		return result;
	}
	
	fselectedPlanAttribIDs(); // ======== calling fselectedPlanAttribIDs();
} );
