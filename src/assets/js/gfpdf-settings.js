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

			/**
			 * Get if on the global PDF settings pages
			 * @return Integer
			 * @since 4.0
			 */
			this.is_settings = function() {
				return $('#tab_PDF').length;
			}

			/**
			 * Check if on the individual PDF form settings pages
			 * @return Integer
			 * @since 4.0
			 */
			this.is_form_settings = function() {
				return $('#tab_pdf').length;
			}

			/**
			 * See if we are on the form settings list page 
			 * @return Integer
			 * @since 4.0
			 */
			this.is_form_settings_list = function() {
				return $('#gfpdf_list_form').length;
			}

			/**
			 * See if we are on the form settings edit page 
			 * @return Integer
			 * @since 4.0
			 */
			this.is_form_settings_edit = function() {
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

			/**
			 * Routing functionality for the 
			 * @return {[type]} [description]
			 */
			this.processFormSettings = function() {		

				if(this.is_form_settings_edit()) {
					this.do_form_settings_edit_page();
				}		

				if(this.is_form_settings_list()) {
					this.do_form_settings_list_page();
				}											
			}

			/**
			 * Process the functionality for the PDF form settings 'list' page
			 * @return void 
			 * @since 4.0
			 */
			this.do_form_settings_list_page = function() {
				/* Set up our delete dialog */
				var $deleteDialog = $( '#delete-confirm' );	

				var deleteButtons = [{
				      	text: GFPDF.pdf_list_delete_confirm,
				      	click: function() {
				      		/* handle ajax call */
				      		$deleteDialog.wpdialog( 'close' );
				      		$elm = $( $deleteDialog.data('elm') );

				      		var data = {
				      			'action': 'gfpdf_list_delete',
				      			'nonce': $elm.data('nonce'),
				      			'fid': $elm.data('fid'),
				      			'pid': $elm.data('id'),
				      		};

				      		self.ajax(data, function(response) {	
				      			if(response.msg) {
				      				self.show_message(response.msg);
				      				var $row = $elm.parents('tr');
				      				$row.css('background', '#ffb8b8').fadeOut().remove();
				      			}

				      			console.log(response);
				      		});

				      	}
				      },
				      {
				      	text: GFPDF.tools_cancel,
				      	click: function() {
				      		/* cancel */
				      		$deleteDialog.wpdialog( 'close' );	
				      	}				      				       
				}];

				/* Add our dleete dialog box */
				this.wp_dialog($deleteDialog, deleteButtons, 300, 175);								

				/* Add live delete listener. Using on ensures nodes added later will have correct listener */
				$('#gfpdf_list_form').on('click', 'a.submitdelete', function() {
					var id = String($(this).data('id'));
					if(id.length > 0) {
						$deleteDialog.wpdialog( 'open' ).data('elm', this);
					}
				});

				/* Add live duplicate listener. Using on ensures nodes added later will have correct listener */
				$('#gfpdf_list_form').on('click', 'a.submitduplicate', function() {
					var id = String($(this).data('id'));
					var that = this;

					if(id.length > 0) {
						/* set up ajax data */
			      		var data = {
			      			'action': 'gfpdf_list_duplicate',
			      			'nonce': $(this).data('nonce'),
			      			'fid': $(this).data('fid'),
			      			'pid': $(this).data('id'),
			      		};

			      		/* do ajax call */
			      		self.ajax(data, function(response) {	
			      			if(response.msg) {
			      				/* provide feedback to use */
			      				self.show_message(response.msg);

			      				/* clone the row to be duplicated */
								var $row    = $(that).parents('tr');
								var $newRow = $row.clone().css('background', '#baffb8');

								/* update the edit links to point to the new location */
			      				$newRow.find('.column-name > a, .edit a').each(function() {									
									var href = $(this).attr('href');
									href     = self.updateURLParameter(href, 'pid', response.pid);
									$(this).attr('href', href);
			      				});

			      				/* Update the name field */
			      				$newRow.find('.column-name > a').html(response.name);

			      				/* Find duplicate and delete elements */
								var $duplicate = $newRow.find('.duplicate a');
								var $delete    = $newRow.find('.delete a');
								var $state     = $newRow.find('.check-column img');

								/* update duplicate ID and nonce pointers so the actions are valid */
								$duplicate.data('id', response.pid);
								$duplicate.data('nonce', response.dup_nonce);								

								/* update delete ID and nonce pointers so the actions are valid */
								$delete.data('id', response.pid);
								$delete.data('nonce', response.del_nonce);

								/* update state ID and nonce pointers so the actions are valid */
								$state.data('id', response.pid);
								$state.data('nonce', response.state_nonce);								

								/* add fix for alternate row background */
								if($row.hasClass('alternate')) {
									$newRow.removeClass('alternate');
									var background = '#FFF';
								} else {
									$newRow.addClass('alternate');
									var background = '#f9f9f9';
								}

								/* add row to node and fade in */
								$newRow.hide().insertAfter($row).fadeIn().animate({backgroundColor: background});
			      			}
			      			console.log(response);
			      		});						
					}
				});	

				/* Add live state listener to chance active / inactive value */	
				$('#gfpdf_list_form').on('click', '.check-column img', function() {
					var id = String($(this).data('id'));
					var that = this;					

					if(id.length > 0) {
						var is_active = that.src.indexOf('active1.png') >= 0;
						if (is_active) {
							that.src = that.src.replace('active1.png', 'active0.png');
							$(that).attr('title', GFPDF.inactive).attr('alt', GFPDF.inactive);
						} else {
							that.src = that.src.replace('active0.png', 'active1.png');
							$(that).attr('title', GFPDF.active).attr('alt', GFPDF.active);
						}	
						
						/* set up ajax data */
			      		var data = {
			      			'action': 'gfpdf_change_state',
			      			'nonce': $(this).data('nonce'),
			      			'fid': $(this).data('fid'),
			      			'pid': $(this).data('id'),			      			
			      		};

			      		/* do ajax call */
			      		self.ajax(data, function(response) {	

			      		});			      		
			      	}
				});		
			}

			/**
			 * Process the individual PDF GF Form Settings Page
			 * @return void 
			 * @since 4.0
			 */
			this.do_form_settings_edit_page = function() {
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

			/**
			 * An AJAX Wrapper function we can use to ajaxify our plugin
			 * @param post Object an object of data to submit to our ajax endpoint. This MUST include an 'nonce' and an 'action'
			 * @param successCallback a callback function
			 * @return void 
			 * @since 4.0
			 */
			this.ajax = function(post, successCallback) {
				$.ajax({
					type : "post",
					dataType : "json",
					url : GFPDF.ajaxurl,
					data : post,
					success: successCallback,
					error: this.ajax_error,
				});
			}	

			/**
			 * Throw an alert when there is an ajax error 
			 * @return void 
			 * @since 4.0
			 */
			this.ajax_error = function() {
				alert(GFPDF.ajax_error);
			}

			this.show_message = function(msg, timeout, error) {
				timeout = typeof timeout !== 'undefined' ? timeout : 4500;
				error = typeof error !== 'undefined' ? error : false;

				var $elm = $('<div id="message">').html('<p>' + msg + '</p>');

				if(error === true) {
					$elm.addClass('error');
				} else {
					$elm.addClass('updated');
				}

				$('.wrap > h2').after($elm);

				setTimeout(function() {
					$elm.slideUp();
				}, timeout);

			}

			/**
			 * http://stackoverflow.com/a/10997390/11236
			 */
			this.updateURLParameter = function(url, param, paramVal) {
			    var newAdditionalURL = "";
			    var tempArray = url.split("?");
			    var baseURL = tempArray[0];
			    var additionalURL = tempArray[1];
			    var temp = "";
			    if (additionalURL) {
			        tempArray = additionalURL.split("&");
			        for (i=0; i<tempArray.length; i++){
			            if(tempArray[i].split('=')[0] != param){
			                newAdditionalURL += temp + tempArray[i];
			                temp = "&";
			            }
			        }
			    }

			    var rows_txt = temp + "" + param + "=" + paramVal;
			    return baseURL + "?" + newAdditionalURL + rows_txt;
			}			
		}	

		var pdf = new GravityPDF();
		pdf.init();	

	});		
})(jQuery);