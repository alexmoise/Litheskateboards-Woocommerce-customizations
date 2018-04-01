
// === PRODUCT OPTIONS SELECT CONDITIONALS - LET'S HAVE THIS MOVED TO A PLUGIN LATER ON ===
// Submit the form (adding product to cart) when Payment Plan option is chosen

jQuery( document ).ready( function () {
	$('.table.variations input[name="attribute_pa_paying-plan"]').on('click', function() {
	    $(this).closest("form").submit();
	});
});


// Add a class to payment options when WIDTH is selected

jQuery( document ).ready( function () {
	$('.table.variations input[name="attribute_pa_width"]').change(function(){
	   if(this.checked) 
	   {
		   $('.attribute-pa_paying-plan').addClass('unhide-by-width');
	   } else {
		   $('.attribute-pa_paying-plan').removeClass('unhide-by-width');
	   }
	});
});


// Add a class to payment options when MODEL is selected

jQuery( document ).ready( function () {
	$('.table.variations input[name="attribute_pa_model"]').change(function(){
	   if(this.checked) 
	   {
		   $('.attribute-pa_paying-plan').addClass('unhide-by-model');
	   } else {
		   $('.attribute-pa_paying-plan').removeClass('unhide-by-model');
	   }
	});
});


// Toggle a class of parent DIV of radio buttons - it's styled further in Additional CSS

jQuery( document ).ready( function () {
	$('div.attribute-pa_model.tr input[type="radio"]').on('change', function(){
		$('input[type="radio"]').siblings().closest('div').removeClass('has-been-selected');
		if(this.checked) 
	   {
		   $(this).closest('div').addClass("has-been-selected", this.checked);
	   } 
	});
});


// Toggle a class of parent DIV of radio buttons that ARE DISABLED or ARE SELECTED - these are styled further in Additional CSS

jQuery( document ).delegate( '#table-variations', 'change', function(event) {
	$(this).unbind('click');
	
	
	if( ('.table.variations input[name="attribute_vert"]').checked)
	{
		$('.attribute-pa_paying-plan').addClass('unhide-by-width unhide-by-model');
	}
	
	
	
	
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
	jQuery( 'input:not([disabled="disabled"])' ).parent('div').toggleClass('has-been-disabled',false);
	jQuery( 'input[type="radio"]:checked' ).parent('div').toggleClass('radio-checked',true);
	jQuery( 'input[type="radio"]:not(:checked)' ).parent('div').toggleClass('radio-checked',false);
	$('*[class=""]').removeAttr('class'); // removing empty "class" attribute, but only when it's empty ;-)
});


// Toggle a class of parent DIV of disabled elements at first window.load anyway

jQuery(window).load(function() {
	jQuery( 'input[disabled="disabled"]' ).parent('div').toggleClass('has-been-disabled',true);
});

// === PRODUCT OPTIONS SELECT CONDITIONALS - END ===




// === DOING IT AGAIN, FOR TEST 2 DECK ===
// Add a class to payment options when WIDTH is selected
/* 
jQuery( document ).ready( function () {
	$('.table.variations input[name="attribute_vert"]').change(function(){
	   if(this.checked) 
	   {
		   $('.attribute-pa_paying-plan').addClass('unhide-by-width unhide-by-model');
	   } else {
		   $('.attribute-pa_paying-plan').removeClass('unhide-by-width unhide-by-model');
	   }
	});
});
*/ 

// Add a class to payment options when MODEL is selected

jQuery( document ).ready( function () {
	$('.table.variations input[name="attribute_street"]').change(function(){
	   if(this.checked) 
	   {
		   $('.attribute-pa_paying-plan').addClass('unhide-by-width unhide-by-model');
	   } else {
		   $('.attribute-pa_paying-plan').removeClass('unhide-by-width unhide-by-model');
	   }
	});
});

// === PRODUCT OPTIONS SELECT CONDITIONALS ==TEST== END ===

