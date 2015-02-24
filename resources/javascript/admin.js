(function($) {	
		var tab = $('#tab_PDF');

		tab.find('.nav-tab-contents:not(:first)').hide();

		tab.find('.nav-tab').click(function() {

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
			var tab = $('#tab_PDF');

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