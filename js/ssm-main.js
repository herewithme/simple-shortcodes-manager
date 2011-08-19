/*
 * Custom javascript for SSM
 */
jQuery(document).ready( function() {
	
	var shortcode_description = jQuery('.shortcode_description');
	
	// Show shortcode description
	jQuery('#add_shortcode_id').change(function (e) {
		
		shortcode_description.hide();
		
		var current_value = jQuery(this).val();
		jQuery('#current_shortcode_description_' + current_value ).show();
	});
	
	shortcode_description.hide();

});