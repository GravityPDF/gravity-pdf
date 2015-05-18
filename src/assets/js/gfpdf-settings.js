/**
 * Gravity PDF Settings JS Logic 
 * Dependancies: backbone, underscore, jquery
 * @since 4.0
 */

(function($) {	

	$(function() {

		/*
		 * Override the gfMergeTagsObj.getTargetElement prototype to better handle CSS special characters in selectors 
		 */
		if(typeof form != 'undefined' && typeof window.gfMergeTags != 'undefined') {
			window.gfMergeTags.getTargetElement = function(elem) {
				console.log('Gravity PDF gfMergeTags.getTargetElement Override Running');

				var $elem    = $( elem );
				var selector = $elem.parents('span.all-merge-tags').data('targetElement');				

				/* escape any meta-characters as per jQuery Spec http://api.jquery.com/category/selectors/ */
		        selector = selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");        

		        return $('#' + selector );		        		        
			}
		}
	
		



		/**
		 * Our Admin controller 
		 * Applies correct JS to settings pages 
		 */
		function GravityPDF () {
			var self = this;

			this.init = function() {
				if(this.is_settings()) {
					this.processSettings();
				}

				if(this.is_form_settings()) {
					this.processFormSettings();
				}
			}

			this.is_settings = function() {
				return $('#tab_PDF').length;
			}

			this.is_form_settings = function() {
				return $('#gfpdf_pdf_form').length;
			}

			this.processSettings = function() {
				var active = $('.nav-tab-wrapper a.nav-tab-active:first').text();

				this.show_tooltips();
				this.setup_select_boxes();

				switch (active) {
					case 'General':
						this.general_settings();						
					break;

					case 'Tools':
						this.tools_settings();
					break;

					case 'Help':
						this.help_settings();
					break;
				}
			}

			this.processFormSettings = function() {				
				this.setup_select_boxes();	
				this.setup_advanced_options();	
				this.setup_required_fields($('#gfpdf_pdf_form'));				
				this.show_tooltips();

				var $secTable    = $('#pdf-general-advanced');
				var $pdfSecurity = $secTable.find('input[name="gfpdf_settings[security]"]');
				var $format      = $secTable.find('input[name="gfpdf_settings[format]"]');

				/*
				 * Add change event to admin restrictions to show/hide dependant fields 
				 */
				$pdfSecurity.change(function() {					
					if($(this).is(':checked')) {
						/* get the format dependancy */
						var format =  $format.filter(':checked').val();

						if($(this).val() === 'No' || format !== 'Standard') {
							/* hide security password / privileges */
							$secTable.find('tr:nth-child(3),tr:nth-child(4)').hide();
						} else {
							/* show security password / privileges */
							$secTable.find('tr:nth-child(3),tr:nth-child(4)').show();
						}

						if(format !== 'Standard') {
							$secTable.find('tr:nth-child(2)').hide();
						} else {
							$secTable.find('tr:nth-child(2)').show();
						}
					}					
				}).trigger('change');	
				
				$format.change(function() {		
					if($(this).is(':checked')) {
						$pdfSecurity.trigger('change');
					}					
				}).trigger('change');

				/*
				 * Add change event to 'chosen' notification item 
				 */
				$("#gfpdf_settings\\[notification\\]").change( function() {
					var not  = $(this).val();
					var $elm = $('input[name="gfpdf_settings[save]"]').parents('tr');
					if(not !== null && not.length > 0) {
						$elm.hide();
					} else {
						$elm.show();
					}
				}).trigger('change');
										
			}

			this.setup_required_fields = function($elm) {
				$elm.find('input[type="submit"]').click(function() {
					$(this).parents('form').addClass('formSubmitted');
				});				

				$elm.find(':input[required=""], :input[required]').each(function() {				
					$(this).parents('tr').find('th').append('<span class="gfield_required">*</span>');
				});				
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

			this.setup_select_boxes = function() {
				$('.gfpdf-chosen').each(function() {
					var width = $(this).css('width');

					$(this).chosen({
						disable_search_threshold: 5,
						width: width,
					});
				});					
			}

			this.setup_advanced_options = function() {
				var $advanced_options  = $('.gfpdf-advanced-options a');

				/*
				 * Show / Hide Advanced options
				 */
				$advanced_options.click(function() {
					var click = this;

					/* toggle our slider */
					$(this).parent().prev().slideToggle(600, function() {
						/* Toggle our link text */
						var text = $(click).text();
						$(click).text(
							text == GFPDF.general_advanced_show ? GFPDF.general_advanced_hide : GFPDF.general_advanced_show
						);						
					});	

					return false;				
				});						
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
						$table.find('tr:nth-child(2)').hide();
					} else {
						/* hide user restrictions and logged out user timeout */
						$table.find('tr:nth-child(2)').show();
					}
				});

				/* setup advanced options */
				this.setup_advanced_options();		
			}

			/**
			 * The tools settings model method 
			 * This sets up and processes any of the JS that needs to be applied on the tools settings tab 
			 * @since 3.8
			 */
			this.tools_settings = function() {
				var $copy            = $('#gfpdf_settings\\[copy\\]'); /* escape braces */
				var $copyDialog      = $( '#setup-templates-confirm' );
				var $uninstall       = $('#gfpdf-uninstall'); 
				var $uninstallDialog = $( '#uninstall-confirm' );				

				/* Set up copy dialog */
				var copyButtons = [{
				      	text: GFPDF.tools_template_copy_confirm,
				      	click: function() {
				      		/* do redirect */
				      		window.location = $copy.attr('href');
				      	}
				      },
				      {
				      	text: GFPDF.tools_cancel,
				      	click: function() {
				      		/* cancel */
				      		$copyDialog.wpdialog( 'close' );	
				      	}				      				       
				}];

				this.wp_dialog($copyDialog, copyButtons, 500, 175);

				$copy.click(function() {
					$copyDialog.wpdialog('open');
				    return false;			
				});	

				/* Set up uninstall dialog */
				var uninstallButtons = [{
			      	text: GFPDF.tools_uninstall_confirm,
			      	click: function() {
			      		/* submit form */
			      		$uninstall.parents('form').submit();
			      	}
			      },
			      {
			      	text: GFPDF.tools_cancel,
			      	click: function() {
			      		/* cancel */
			      		$uninstallDialog.wpdialog( 'close' );	
			      	}				      				       
			    }];	

			    this.wp_dialog($uninstallDialog, uninstallButtons, 500, 175);			

				$uninstall.click(function() {
					$uninstallDialog.wpdialog('open');
				    return false;			
				});							    
			}

			this.wp_dialog = function($elm, buttonsList, boxWidth, boxHeight) {
				$elm.wpdialog({
				  autoOpen: false,
			      resizable: false,
			      draggable: false,
			      width: "auto",			      
			      height: boxHeight,
			      modal: true,
				  dialogClass: 'wp-dialog',
				  zIndex: 300000,		      
			      buttons: buttonsList,
			      create: function( event, ui ) {
			      	$(this).css('maxWidth', boxWidth + 'px');
			      },
			      open: function() {
			      	$(this).siblings('.ui-dialog-buttonpane').find('button:eq(1)').focus(); 

		            $('.ui-widget-overlay').bind('click', function() {
		                $elm.wpdialog('close');
		            })			      	
			      }
			    });					
			}


			this.help_settings = function() {

			}

			/**
			 * [get_tooltip description]
			 * @param  {[type]} html [description]
			 * @return {[type]}      [description]
			 */
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

		var pdf = new GravityPDF();
		pdf.init();	

	});		
})(jQuery);