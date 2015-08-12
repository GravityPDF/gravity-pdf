/**
 * Gravity PDF Entries
 * Dependancies: jQuery
 * @since 4.0
 */

(function($) {

	var GFPDF = {

		/**
		 * Do a better hover when accessing the PDF submenu
		 * This allows for a longer timeout before the submenu is hidden again
		 * @since 4.0
		 */
		PDFSubmenuHover: function() {
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
		}
	};

	/**
	 * Fires on the Document Ready Event (the same as $(document).ready(function() { ... });)
	 * @since 4.0
	 */
	$(function() {
		GFPDF.PDFSubmenuHover();
	});
})(jQuery);