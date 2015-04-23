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
						this.do_conditional_general_settings();						
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

			this.do_conditional_general_settings = function() {				
				var $table             = $('#pdf-general-security');
				var $adminRestrictions = $table.find('input[name="gfpdf_settings[limit_to_admin]"]');
				var $userRestrictions  = $table.find('input[name="gfpdf_settings[limit_to_user]"]');

				/*
				 * Add change event to admin restrictions to show/hide dependant fields 
				 */
				$adminRestrictions.change(function() {					
					if($(this).val() === 'Yes') {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(2)').hide();
						$table.find('tr:nth-child(3)').hide();
					} else {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(2)').show();
						if($userRestrictions.val() !== 'Yes') {
							$table.find('tr:nth-child(3)').show();
						}
					}
				});

				/*
				 * Add change event to logged-out restrictions to show/hide dependant fields 
				 */
				$userRestrictions.change(function() {
					if($(this).val() === 'Yes') {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(3)').hide();
					} else {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(3)').show();
					}
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