(function($) {
	jQuery(document).ready( function($) {
		jQuery( '.nav-tab-contents:not(:first)' ).hide();
	 
		jQuery( '.nav-tab' ).click(function(){
			switch_tabs( $(this) );
			return false;
		});
		
		
		/*
		 * Check if a #hash exists and pass it to switch_tabs
		 */	
		 if(window.location.hash) {
			switch_tabs( $('.nav-tab[href="' + window.location.hash + '"]') ) ; 
		 }
		 
		 /*
		  * Validate Support Form
		  */
		  jQuery( '#support-request-button' ).click(function() {
			 if(validate_form() === true)
			 {
				return false; 
			 }
			 else
			 {
				ajax_request(); 
				return false;
			 }
		  });
	 
	});
	
	function ajax_request()
	{
		/*
		 * Create an AJAX Request
		 */
		 $('#support-request-button').after('<span class="icon-spinner icon-spin">');
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
		})
		.done(function( results ) {
			$('.icon-spinner').remove();
			
			if(results.error)
			{
				if(results.error.email)
				{
			 		var $email =  $('#email-address');				
					$email.addClass('error').after($('<span class="icon-remove-sign">'));
				}
				
				if(results.error.supportType)
				{
					 var $support = $('#support-type');	
					 $support.addClass('error').after($('<span class="icon-remove-sign">'));						
				}
				
				if(results.error.comments)
				{
					 var $comments = $('#comments');	
					 $comments.addClass('error').after($('<span class="icon-remove-sign">'));				
				}	
				
				$('#support-request-button').after('<span class="error">' + results.error.msg + '</span>');							
			}
			else if (results.msg)
			{
				$('#support-request-button').after('<span class="msg">' + results.msg + '</span>');
			}
		});		 
	};
	 
	function switch_tabs(obj)
	{
		//  Test to see if the menu tab is already active
		//  Only process the click if the tab is inactive
		if ( ! obj.hasClass( 'nav-tab-active' ) )
		{
			//  Hide the active menu tab and all the contents panels
			jQuery( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
			jQuery( '.nav-tab-contents' ).hide();
	 
			//  Get the value of the ‘rel’ attribute of the selected element object
			//  Translate the value into the id reference of the target panel
			var id = obj.attr( 'href' );
	 
			//  Set the selected menu tab to active
			//  Show the associated contents panel with the ID
			//  that matches the object ‘rel’ identifier
			obj.addClass( 'nav-tab-active' );
			jQuery( id ).show( );
		}
	};
	
	function validate_form()
	{
		var error = false;
		/*
		 * Check email address is filled out
		 */
		 var $email =  $('#email-address');
		 var $comments = $('#comments');
		 
		 /*
		  * Reset the errors
		  */
		  $email.removeClass('error');
		  $comments.removeClass('error');	  
		  $('#support .icon-remove-sign').remove();
		 
		 if($email.val().length == 0)
		 {
			 $email.addClass('error').after($('<span class="icon-remove-sign">'));
			 error = true;	 
		 }
		 
		 if($comments.val().length == 0)
		 {
			 $comments.addClass('error').after($('<span class="icon-remove-sign">'));
			 error = true;
		 }
		 return error;
	};
})(jQuery);