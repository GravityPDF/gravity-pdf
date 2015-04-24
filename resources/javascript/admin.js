(function($) {	
		function GravityPDF () {
			var self = this;

			this.init = function() {
				if(this.is_settings()) {
					this.processSettings();
				}
			}

			this.is_settings = function() {
				return $('#tab_PDF').length;
			}

			this.processSettings = function() {
				var active = $('.nav-tab-wrapper a.nav-tab-active:first').text();

				this.show_tooltips();

				switch (active) {
					case 'General':
						this.general_settings();						
					break;

					case 'Tools':
						this.tools_settings();
					break;
				}
			}

			this.show_tooltips = function() {
				$('.gf_hidden_tooltip').each(function() {
					$(this)
					.parent()
					.siblings('th:first')
					.append(' ')
					.append(
						self.get_tooltip($(this).html())
					);

					$(this).remove();
				});

				gform_initialize_tooltips();
			}

			/**
			 * The general settings model method 
			 * This sets up and processes any of the JS that needs to be applied on the general settings tab 
			 * @since 3.8
			 */
			this.general_settings = function() {				
				var $table             = $('#pdf-general-security');
				var $adminRestrictions = $table.find('input[name="gfpdf_settings[limit_to_admin]"]');
				var $userRestrictions  = $table.find('input[name="gfpdf_settings[limit_to_user]"]');

				/*
				 * Add change event to admin restrictions to show/hide dependant fields 
				 */
				$adminRestrictions.change(function() {					
					if($(this).val() === 'Yes') {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(2)').fadeOut();
						$table.find('tr:nth-child(3)').fadeOut();
					} else {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(2)').fadeIn();

						if($userRestrictions.parent().find(':checked').val() !== 'Yes') {
							$table.find('tr:nth-child(3)').fadeIn();
						}
					}
				});

				/*
				 * Add change event to logged-out restrictions to show/hide dependant fields 
				 */
				$userRestrictions.change(function() {
					if($(this).val() === 'Yes') {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(3)').fadeOut();
					} else {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(3)').fadeIn();
					}
				});
			}

			/**
			 * The tools settings model method 
			 * This sets up and processes any of the JS that needs to be applied on the tools settings tab 
			 * @since 3.8
			 */
			this.tools_settings = function() {
				var $reinstall = $('#gfpdf_settings\\[reinstall\\]'); /* escape braces */
				var $dialog    = $( "#reinstall-confirm" );

				$reinstall.click(function() {
					var link = this;

					$dialog.wpdialog({
				      resizable: false,
				      draggable: false,
				      width: 350,
				      height:200,
				      modal: true,
					  dialogClass: 'wp-dialog',
					  zIndex: 300000,		      
				      buttons: [{
				      	text: GFPDF.tools_reinstall_confirm,
				      	click: function() {
				      		/* do redirect */
				      		window.location = link.href;
				      	}
				      },
				      {
				      	text: GFPDF.tools_reinstall_cancel,
				      	click: function() {
				      		/* cancel */
				      		$dialog.wpdialog( "close" );	
				      	}				      				       
				      }]
				    });	

				    return false;			
				});		    
			}

			this.get_tooltip = function(html) {
				var $a = $('<a>');
				var $i = $('<i class="fa fa-question-circle">');				

				$a.append($i);
				$a.addClass('gf_tooltip tooltip');
				$a.click(function() {
					return false;
				});
				
				$a.attr('title', html);
				
				return $a;				
			}		
		}	

		$(document).ready(function() {
			var pdf = new GravityPDF();
			pdf.init();
		});
})(jQuery);