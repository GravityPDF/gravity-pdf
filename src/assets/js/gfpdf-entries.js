/**
 * Gravity PDF Entries
 * Dependancies: jQuery
 * @since 4.0
 */

(function($) {
	$(function() {
	     $('table .gf_form_action_has_submenu').hover(function() {
	         clearTimeout($(this).data('timeout'));
	         $(this).find('.gf_submenu').show();
	     }, function() {
	         var self = this;
	         var t = setTimeout(function() {
	            $(self).find('.gf_submenu').hide();
	        }, 350);
	         $(this).data('timeout', t);
	     });
	});
})(jQuery);