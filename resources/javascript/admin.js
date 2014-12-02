(function($) {	

	$(document).ready(function($) {
		var tab = $('#tab_PDF');

		tab.find('.nav-tab-contents:not(:first)').hide();

		tab.find('.nav-tab').click(function() {
			switch_tabs($(this));
			return false;
		});

		/*
		 * Check if a #hash exists and pass it to switch_tabs
		 */
		if (window.location.hash) {
			switch_tabs(tab.find('.nav-tab[href="' + window.location.hash + '"]'));
		}

		/*
		 * Validate Support Form
		 */
		$('#support-request-button').click(function() {
			if (validate_form() === true) {
				return false;
			} else {
				ajax_request();
				return false;
			}
		});

	});

	function ajax_request() {
		/*
		 * Create an AJAX Request
		 */		
		var spinner = $('<img alt="Loading" class="gfspinner" src="' + GFPDF.GFbaseUrl + '/images/spinner.gif" />');
		$('#support-request-button').after(spinner);

		$('span.msg').remove();
		$('span.error').remove();

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: 'json',
			data: {
				action: 'support_request',
				nonce: $('#pdf_settings_nonce_field').val(),
				email: $('#email-address').val(),
				supportType: $('#support-type').val(),
				comments: $('#comments').val()
			}
		}).done(function(results) {
				$('.gfspinner').remove();

				if (results.error) {
					if (results.error.email) {
						var $email = $('#email-address');
						$email.addClass('error').after($('<span class="icon-remove-sign">'));
					}

					if (results.error.supportType) {
						var $support = $('#support-type');
						$support.addClass('error').after($('<span class="icon-remove-sign">'));
					}

					if (results.error.comments) {
						var $comments = $('#comments');
						$comments.addClass('error').after($('<span class="icon-remove-sign">'));
					}

					$('#support-request-button').after('<span class="error">' + results.error.msg + '</span>');
				} else if (results.msg) {
					$('#support-request-button').after('<span class="msg">' + results.msg + '</span>');
				}
			});
	}

	function switch_tabs(obj) {
		//  Test to see if the menu tab is already active
		//  Only process the click if the tab is inactive
		if (!obj.hasClass('nav-tab-active')) {
			var tab = $('#tab_PDF');

			//  Hide the active menu tab and all the contents panels
			tab.find('.nav-tab-active').removeClass('nav-tab-active');
			tab.find('.nav-tab-contents').hide();

			//  Get the value of the ‘rel’ attribute of the selected element object
			//  Translate the value into the id reference of the target panel
			var id = obj.attr('href');

			//  Set the selected menu tab to active
			//  Show the associated contents panel with the ID
			//  that matches the object ‘rel’ identifier
			obj.addClass('nav-tab-active');
			$(id).show();
		}
	}

	function validate_form() {
		var error = false;
		/*
		 * Check email address is filled out
		 */
		var $email = $('#email-address');
		var $comments = $('#comments');

		/*
		 * Reset the errors
		 */
		$email.removeClass('error');
		$comments.removeClass('error');
		$('#support .icon-remove-sign').remove();

		if ($email.val().length == 0) {
			$email.addClass('error').after($('<span class="icon-remove-sign">'));
			error = true;
		}

		if ($comments.val().length == 0) {
			$comments.addClass('error').after($('<span class="icon-remove-sign">'));
			error = true;
		}
		return error;
	}
})(jQuery);