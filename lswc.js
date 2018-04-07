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
	$( 'input[type="radio"]:not(:checked)'	).parent('div').toggleClass('radio-checked',false);
	if ($(this).parents().find('input[type="radio"]').is(':checked')) // "payment plan" is hidden by default (in CSS), here add class to unhide it if any other attribute is selected
	{
		$('.attribute-pa_paying-plan').toggleClass('unhide-payments',true);
	} else {
		$('.attribute-pa_paying-plan').toggleClass('unhide-payments',false);
	}
	$( '*[class=""]' ).removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
});
// === Submit the form automatically (adding product to cart) when Payment Plan option is chosen
jQuery( document ).delegate( '.table.variations input[name="attribute_pa_paying-plan"]', 'click', function(event) {
	$(this).unbind('click');
	$(this).closest("form").submit();
});
// === Removing attributes "name" part
var board_models = ['Street', 'Vert']; // will need to sync this with attributes from Woocommerce
jQuery(window).on('load', function(a) {
	for (var i = 0; i < board_models.length; i++) {
		// alert ('hei: ' + board_models[i]); // checking the proper attribute address
		$('.table.variations .value.td label').each( function(c) {
			oriText = $(this).html();
			newText = oriText.replace(board_models[i] + ' ','');
			$(this).html(newText);
		}); 
	}
}); 
