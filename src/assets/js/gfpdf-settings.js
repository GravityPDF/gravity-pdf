/**
 * Gravity PDF Settings JS Logic
 * Dependancies: backbone, underscore, jquery
 * @since 4.0
 */

(function ($) {

  /**
   * Fires on the Document Ready Event (the same as $(document).ready(function() { ... });)
   * @since 4.0
   */
  $(function () {

    /**
     * Our Admin controller
     * Applies correct JS to our Gravity PDF pages
     * @since 4.0
     */
    function GravityPDF () {

      /**
       * A reference to the GravityPDF object when 'this' refers to a different object
       * Usage is inside AJAX closures. All other functions should use $.proxy() to set this appropriately
       * @type Object
       * @since 4.0
       */
      var self = this

      /**
       * Process the correct settings area (the global PDF settings or individual form PDF settings)
       * Also set up any event listeners needed
       * @return void
       * @since 4.0
       */
      this.init = function () {

        /* Process any common functions */
        this.initCommon()

        /* Process the global PDF settings */
        if (this.isSettings()) {
          this.processSettings()
        }

        /* Process the individual form PDF settings */
        if (this.isFormSettings()) {
          this.processFormSettings()
        }
      }

      /**
       * Initialise any common elements
       * @return void
       * @since 4.0
       */
      this.initCommon = function () {

        /* Change some Gravity Forms parameters */
        this.setupGravityForms()

        /* If we have a upload field handle the logic */
        this.doUploadListener()

        /* If we have a colour picker handle the logic */
        this.doColorPicker()

        /* If we have any select boxes to handle */
        this.setupSelectBoxes()

        /* Enable tooltips, if needed */
        this.showTooltips()

        /* Setup custom paper size, if needed */
        this.setupCustomPaperSize()

        /* Setup toggled fields, if needed */
        this.setupToggledFields()

        /* Setup our template loader, if needed */
        this.setupDynamicTemplateFields()

        /* Setup license deactivation, if needed */
        this.setupLicenseDeactivation()
      }

      /**
       * Replace some of Gravity Forms JS variables so it functions correctly with our PDF version
       *
       * @since 4.1
       */
      this.setupGravityForms = function () {
        /**
         * Check if the global gf_vars has been set and if so replace the .thisFormButton, .show, .hide objects with our
         * customised options.
         * @since 4.0
         */
        if (typeof gf_vars !== 'undefined') {
          gf_vars.thisFormButton = GFPDF.conditionalText
          gf_vars.show = GFPDF.enable
          gf_vars.hide = GFPDF.disable
        }

        /*
         * Backwards compatibility support prior to Gravity Forms 2.3
         *
         * Override the gfMergeTagsObj.getTargetElement prototype to better handle CSS special characters in selectors
         * This is because Gravity Forms doesn't correctly espace meta-characters such a [ and ] (which we use extensively as IDs)
         * This functionality assists with the merge tag loader
         * @since 4.0
         */
        if (window.gfMergeTags && typeof form != 'undefined') {
          window.gfMergeTags.getTargetElement = this.resetGfMergeTags
        }
      }

      /**
       * Get if on the global PDF settings pages
       * @return Integer
       * @since 4.0
       */
      this.isSettings = function () {
        return $('#tab_PDF').length
      }

      /**
       * Check if on the individual PDF form settings pages
       * @return Integer
       * @since 4.0
       */
      this.isFormSettings = function () {
        return $('#tab_pdf').length
      }

      /**
       * See if we are on the form settings list page
       * @return Integer
       * @since 4.0
       */
      this.isFormSettingsList = function () {
        return $('#gfpdf_list_form').length
      }

      /**
       * See if we are on the form settings edit page
       * @return Integer
       * @since 4.0
       */
      this.isFormSettingsEdit = function () {
        return $('#gfpdf_pdf_form').length
      }

      /**
       * Check the current active PDF settings page
       * @return String
       * @since 4.0
       */
      this.getCurrentSettingsPage = function () {
        if (this.isSettings()) {
          return $('.nav-tab-wrapper a.nav-tab-active:first').data('id')
        }
        return ''
      }

      /**
       * Process the global settings page
       * @return void
       * @since 4.0
       */
      this.processSettings = function () {

        /* Ensure the Gravity Forms settings navigation (Form Settings / Notifications / Confirmation) has the 'tab' URI stripped from it */
        this.cleanupGFNavigation()

        /* Run our direct PDF status check */
        this.runPDFAccessCheck()

        /* Run the appropriate settings page */
        switch (this.getCurrentSettingsPage()) {
          case 'general':
            this.generalSettings()
            break

          case 'tools':
            this.toolsSettings()
            break
        }
      }

      /**
       * Routing functionality for the individual form settings page
       * @return void
       * @since 4.0
       */
      this.processFormSettings = function () {

        /* Process PDF list page */
        if (this.isFormSettingsList()) {
          this.doFormSettingsListPage()
        }

        /* Process single edit page */
        if (this.isFormSettingsEdit()) {
          this.doFormSettingsEditPage()
        }
      }

      /**
       * Process the individual PDF GF Form Settings Page
       * @return void
       * @since 4.0
       */
      this.doFormSettingsEditPage = function () {

        this.setupRequiredFields($('#gfpdf_pdf_form'))
        /* highlight which fields are required and disable in-browser validation */
        this.setupPdfTabs()
        this.handleSecurityConditionals()
        this.handlePDFConditionalLogic()
        this.handleOwnerRestriction()
        this.toggleFontAppearance($('#gfpdf_settings\\[template\\]').data('template_group'))
        this.toggleAppearanceTab()

        /*
         * Workaround for Firefix TinyMCE Editor Bug NS_ERROR_UNEXPECTED (http://www.tinymce.com/develop/bugtracker_view.php?id=3152) when loading wp_editor via AJAX
         * Manual save TinyMCE editors on form submission
         */
        $('#gfpdf_pdf_form').submit(function () {
          try {
            tinyMCE.triggerSave()
          } catch (e) {}

        })

        /* Add listener on submit functionality */
        $('#gfpdf_pdf_form').submit(function () {
          /* JSONify the conditional logic so we can pass it through the form and use it in PHP (after running json_decode) */
          $('#gfpdf_settings\\[conditionalLogic\\]').val(jQuery.toJSON(window.gfpdf_current_pdf.conditionalLogic))
        })

      }

      /**
       * Handles our DOM security conditional logic based on the user selection
       * @return void
       * @since 4.0
       */
      this.handleSecurityConditionals = function () {

        /* Get the appropriate elements for use */
        var $secTable = $('#pdf-general-advanced')
        var $pdfSecurity = $secTable.find('input[name="gfpdf_settings[security]"]')
        var $format = $secTable.find('input[name="gfpdf_settings[format]"]')

        /* Add change event to admin restrictions to show/hide dependant fields */
        $pdfSecurity.change(function () {

          if ($(this).is(':checked')) {

            /* Get the format dependancy */
            var format = $format.filter(':checked').val()

            if ($(this).val() === GFPDF.no || format !== GFPDF.standard) {
              /* hide security password / privileges */
              $secTable.find('tr:nth-child(3),tr:nth-child(4),tr:nth-child(5):not(.gfpdf-hidden)').hide()
            } else {
              /* show security password / privileges */
              $secTable.find('tr:nth-child(3),tr:nth-child(4),tr:nth-child(5):not(.gfpdf-hidden)').show()
            }

            if (format !== GFPDF.standard) {
              $secTable.find('tr:nth-child(2)').hide()
            } else {
              $secTable.find('tr:nth-child(2)').show()
            }
          }

        }).trigger('change')

        /* The format field effects the security field. When it changes it triggers the security field as changed */
        $format.change(function () {
          if ($(this).is(':checked')) {
            $pdfSecurity.trigger('change')
          }
        }).trigger('change')
      }

      /**
       * Add GF JS filter to change the conditional logic object type to our PDF
       * @return Object
       * @since 4.0
       */
      this.handlePDFConditionalLogic = function () {

        gform.addFilter('gform_conditional_object', function (object, objectType) {
          if (objectType === 'gfpdf') {
            return window.gfpdf_current_pdf
          }
          return object
        })

        /* Add change event to conditional logic field */
        $('#gfpdf_conditional_logic').change(function () {

          /* Only set up a .conditionalLogic object if it doesn't exist */
          if (typeof window.gfpdf_current_pdf.conditionalLogic == 'undefined' && $(this).prop('checked')) {

            window.gfpdf_current_pdf.conditionalLogic = new ConditionalLogic()
          } else if (!$(this).prop('checked')) {

            window.gfpdf_current_pdf.conditionalLogic = null
          }
          ToggleConditionalLogic(false, 'gfpdf')

        }).trigger('change')
      }

      /**
       * Show / Hide the Restrict Owner when `Enable Public Access` is set to "Yes"
       * @since 4.0
       */
      this.handleOwnerRestriction = function () {

        var $table = $('#gfpdf-advanced-pdf-options')
        var $publicAccess = $table.find('input[name="gfpdf_settings[public_access]"]')

        /*
         * Add change event to admin restrictions to show/hide dependant fields
         */
        $publicAccess.change(function () {

          if ($(this).is(':checked')) {
            if ($(this).val() === 'Yes') {
              /* hide user restrictions  */
              $table.find('tr:nth-child(8)').hide()
            } else {
              /* show user restrictions */
              $table.find('tr:nth-child(8)').show()
            }
          }
        }).trigger('change')
      }

      /**
       * Process the functionality for the PDF form settings 'list' page
       * @return void
       * @since 4.0
       */
      this.doFormSettingsListPage = function () {

        this.setupAJAXListDeleteListener()
        this.setupAJAXListDuplicateListener()
        this.setupAJAXListStateListener()
      }

      /**
       * Handles the state change of a PDF list item via AJAX
       * @return void
       * @since 4.0
       */
      this.setupAJAXListStateListener = function () {

        /* Add live state listener to change active / inactive value */
        $('#gfpdf_list_form').on('click', '.check-column img', function () {
          var id = String($(this).data('id'))
          var that = this

          if (id.length > 0) {
            var is_active = that.src.indexOf('active1.png') >= 0

            if (is_active) {
              that.src = that.src.replace('active1.png', 'active0.png')
              $(that).attr('title', GFPDF.inactive).attr('alt', GFPDF.inactive)
            } else {
              that.src = that.src.replace('active0.png', 'active1.png')
              $(that).attr('title', GFPDF.active).attr('alt', GFPDF.active)
            }

            /* Set up ajax data */
            var data = {
              'action': 'gfpdf_change_state',
              'nonce': $(this).data('nonce'),
              'fid': $(this).data('fid'),
              'pid': $(this).data('id'),
            }

            /* Do ajax call */
            self.ajax(data, function (response) {
              /* Don't do anything with a successful response */
            })
          }
        })
      }

      /**
       * Handles the duplicate of a PDF list item via AJAX and fixes up all the nonce actions
       * @return void
       * @since 4.0
       */
      this.setupAJAXListDuplicateListener = function () {

        /* Add live duplicate listener */
        $('#gfpdf_list_form').on('click', 'a.submitduplicate', function () {

          var id = String($(this).data('id'))
          var that = this

          /* Add spinner */
          var $spinner = $('<img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner gfpdf-spinner-small" />')

          /* Add our spinner */
          $(this).after($spinner).parent().parent().attr('style', 'position:static; visibility: visible;')

          if (id.length > 0) {
            /* Set up ajax data */
            var data = {
              'action': 'gfpdf_list_duplicate',
              'nonce': $(this).data('nonce'),
              'fid': $(this).data('fid'),
              'pid': $(this).data('id'),
            }

            /* Do ajax call */
            self.ajax(data, function (response) {
              if (response.msg) {

                /* Remove the spinner */
                $(that).parent().parent().attr('style', '').find('.gfpdf-spinner').remove()

                /* Provide feedback to use */
                self.show_message(response.msg)

                /* Clone the row to be duplicated */
                var $row = $(that).parents('tr')
                var $newRow = $row.clone().css('background', '#baffb8')

                /* Update the edit links to point to the new location */
                $newRow.find('.column-name > a, .edit a').each(function () {
                  var href = $(this).attr('href')
                  href = self.updateURLParameter(href, 'pid', response.pid)
                  $(this).attr('href', href)
                })

                /* Update the name field */
                $newRow.find('.column-name > a').html(response.name)

                /* Find duplicate and delete elements */
                var $duplicate = $newRow.find('.duplicate a')
                var $delete = $newRow.find('.delete a')
                var $state = $newRow.find('.check-column img')
                var $shortcode = $newRow.find('.column-shortcode input')

                /* Update duplicate ID and nonce pointers so the actions are valid */
                $duplicate.data('id', response.pid)
                $duplicate.data('nonce', response.dup_nonce)

                /* Update delete ID and nonce pointers so the actions are valid */
                $delete.data('id', response.pid)
                $delete.data('nonce', response.del_nonce)

                /* update state ID and nonce pointers so the actions are valid */
                $state.data('id', response.pid)
                $state.data('nonce', response.state_nonce)

                /* Update our shortcode ID */
                var shortcodeValue = $shortcode.val()
                shortcodeValue = shortcodeValue.replace(id, response.pid)
                $shortcode.val(shortcodeValue)

                /* Add fix for alternate row background */
                var background = ''
                if ($row.hasClass('alternate')) {
                  $newRow.removeClass('alternate')
                  background = '#FFF'
                } else {
                  $newRow.addClass('alternate')
                  background = '#f9f9f9'
                }

                /* Add fix for toggle image */
                var toggle_src = $state.attr('src')
                $state
                  .attr('title', GFPDF.inactive)
                  .attr('alt', GFPDF.inactive)
                  .attr('src', toggle_src.replace('active1.png', 'active0.png'))

                /* Add row to node and fade in */
                $newRow.hide().insertAfter($row).fadeIn().animate({backgroundColor: background})
              }
            })
          }
        })
      }

      /**
       * Check if the last item was just deleted
       */
      this.maybeShowEmptyRow = function () {
        var $container = $('#gfpdf_list_form tbody')

        if ($container.find('tr').length === 0) {
          var $row = $('<tr>').addClass('no-items')
          var $cell = $('<td>').attr('colspan', '5').addClass('colspanchange')
          var $add_new = $('<a>').attr('href', $('#add-new-pdf').attr('href')).append(GFPDF.letsGoCreateOne + '.')
          $cell.append(GFPDF.thisFormHasNoPdfs).append(' ').append($add_new)
          $row.append($cell)
          $container.append($row)
        }
      }

      /**
       * Handles the deletion of a PDF list item via AJAX
       * @return void
       * @since 4.0
       */
      this.setupAJAXListDeleteListener = function () {

        /* Set up our delete dialog */
        var $deleteDialog = $('#delete-confirm')

        var deleteButtons = [{
          text: GFPDF.delete,
          click: function () {
            /* handle ajax call */
            $deleteDialog.wpdialog('close')
            $elm = $($deleteDialog.data('elm'))

            /* Add spinner */
            var $spinner = $('<img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner gfpdf-spinner-small" />')

            /* Add the spinner */
            $elm.append($spinner).parent().parent().attr('style', 'position:static; visibility: visible;')

            var data = {
              'action': 'gfpdf_list_delete',
              'nonce': $elm.data('nonce'),
              'fid': $elm.data('fid'),
              'pid': $elm.data('id'),
            }

            self.ajax(data, function (response) {
              if (response.msg) {
                /* Remove spinner */
                $elm.parent().parent().attr('style', '').find('.gfpdf-spinner').remove()

                self.show_message(response.msg)
                var $row = $elm.parents('tr')
                $row.css('background', '#ffb8b8').fadeOut(400, function () {
                  this.remove()
                  self.maybeShowEmptyRow()
                })
              }

              $deleteDialog.data('elm', null)
            })

          }
        },
          {
            text: GFPDF.cancel,
            click: function () {
              /* cancel */
              $deleteDialog.wpdialog('close').data('elm', null)
            }
          }]

        /* Add our delete dialog box */
        this.wp_dialog($deleteDialog, deleteButtons, 300, 175)

        /* Add live delete listener */
        $('#gfpdf_list_form').on('click', 'a.submitdelete', function () {
          var id = String($(this).data('id'))
          if (id.length > 0 && !$deleteDialog.data('elm')) {
            /* Allow responsiveness */
            self.resizeDialogIfNeeded($deleteDialog, 300, 175)

            $deleteDialog.wpdialog('open').data('elm', this)
          }
        })
      }

      /**
       * Handle our AJAX tabs to make it easier to navigate around our settings
       * @return void
       * @since 4.0
       */
      this.setupPdfTabs = function () {

        /* Hide all containers except the first one */
        $('.gfpdf-tab-container').not(":eq(0)").hide()

        /* Add click handler when our nav is selected */
        $('.gfpdf-tab-wrapper a').click(function () {

          /* Reset the active class */
          $(this).parents('ul').find('a').removeClass('current')

          /* Add the new active class */
          $(this).addClass('current').blur()

          /* Hide all containers */
          $('.gfpdf-tab-container').hide()

          /* Show new active container */
          $($(this).attr('href')).show()

          return false

        })
      }

      /**
       * Add change event listeners on our toggle params and toggle the container
       * @return void
       * @since 4.0
       */
      this.setupToggledFields = function () {

        $('form').off('change', '.gfpdf-input-toggle').on('change', '.gfpdf-input-toggle', function () {

          var $container = $(this).parent().next()

          /* Currently checked so hide out input and if cotains rich_text, textarea or input we will delete values */
          if ($(this).prop('checked')) {
            $container.slideDown('slow')
          } else {
            $container.slideUp('slow')

            /* Remove TinyMCE Content */
            $container.find('.wp-editor-area').each(function () {
              var editor = tinyMCE.get($(this).attr('id'))

              if (editor !== null) {
                editor.setContent('')
              }

            })

            /* Remove textarea content */
            $container.find('textarea').each(function () {
              $(this).val('')
            })
          }
        })
      }

      /**
       * PDF Templates can assign their own custom settings which can enhance a template
       * This function setups the required listeners and functionality to allow this behaviour
       * @return return
       * @since 4.0
       */
      this.setupDynamicTemplateFields = function () {

        /* Add change listener to our template */
        $('#gfpdf_settings\\[template\\]').off('change').change(function () {

          /* Add spinner */
          var $spinner = $('<img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" />')

          $(this).next().after($spinner)

          var data = {
            'action': 'gfpdf_get_template_fields',
            'nonce': GFPDF.ajaxNonce,
            'template': $(this).val(),
            'type': $(this).attr('id'),
            'id': $('#gform_id').val(),
            'gform_pdf_id': $('#gform_pdf_id').val(),
          }

          self.ajax(data, function (response) {

            /* Remove our UI loader */
            $spinner.remove()

            /* Reset our legacy Advanced Template option */
            $('input[name="gfpdf_settings[advanced_template]"][value="No"]').prop("checked", true).trigger('change')

            /* Only process if the response is valid */
            if (response.fields) {

              /* Backwards compatibility support prior to Gravity Forms 2.3 */
              if (window.gfMergeTags) {
                /* Remove any existing mergetag-marked inputs so they aren't processed twice after we add our new fields to the DOM */
                $('.merge-tag-support').removeClass('merge-tag-support')
                $('.all-merge-tags a.open-list').off('click')
              }

              /* Remove any previously loaded editors to prevent conflicts loading an editor with same name */
              $.each(response.editors, function (index, value) {

                var editor = tinyMCE.get(value)
                if (editor !== null) {
                  /* Bug Fix for Firefox - http://www.tinymce.com/develop/bugtracker_view.php?id=3152 */
                  try {
                    tinyMCE.remove(editor)
                  } catch (e) {}
                }

              })

              /* Replace the custom appearance with the AJAX response fields */
              $('#pdf-custom-appearance').hide().html(response.fields).fadeIn()

              /* Ensure our template nav item isn't hidden */
              $('#gfpdf-custom-appearance-nav').show()

              /* Load our new editors */
              self.loadTinyMCEEditor(response.editors, response.editor_init)

              /* reinitialise new dom elements */
              self.initCommon()
              self.doMergetags()

            } else {
              /* Hide our template nav item as there are no fields and clear our the HTML */
              $('#gfpdf-custom-appearance-nav').hide()
              $('#pdf-custom-appearance').html('')
            }

            /* Check if we should hide or show our font fields */
            if (response.template_type) {
              self.toggleFontAppearance(response.template_type)
            }
          })
        })
      }

      /**
       * Handles individual add-on license key deactivation via AJAX
       * @since 4.2
       */
      this.setupLicenseDeactivation = function () {
        $('.gfpdf-deactivate-license').click(function () {
          /* Do AJAX call so user can deactivate license */
          var $container = $(this).parent()
          $container.find('.gf_settings_description label').html('')

          /* Add spinner */
          var $spinner = $('<img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" />')

          /* Add our spinner */
          $(this).append($spinner)

          /* Set up ajax data */
          var slug = $(this).data('addon-name')

          var data = {
            'action': 'gfpdf_deactivate_license',
            'addon_name': slug,
            'license': $(this).data('license'),
            'nonce': $(this).data('nonce'),
          }

          /* Do ajax call */
          self.ajax(data, function (response) {

            /* Remove our loading spinner */
            $spinner.remove()

            if (response.success) {
              /* cleanup inputs */
              $('#gfpdf_settings\\[license_' + slug + '\\]').val('')
              $('#gfpdf_settings\\[license_' + slug + '_message\\]').val('')
              $('#gfpdf_settings\\[license_' + slug + '_status\\]').val('')
              $container.find('i').remove()
              $container.find('a').remove()

              $container.find('.gf_settings_description label').html(response.success)
            } else {
              /* Show error message */
              $container.find('.gf_settings_description label').html(response.error)
            }
          })

          return false
        })
      }

      /**
       * Check if the template type is 'legacy' and hide the font type, size and colour, otherwise show those fields
       * @param type
       * @since 4.0
       */
      this.toggleFontAppearance = function (type) {
        var $rows = $('#pdf-general-appearance').find('tr.gfpdf_font_type, tr.gfpdf_font_size, tr.gfpdf_font_colour')

        /* Hide our font fields if processing a legacy template */
        if (type == 'legacy') {
          $rows.hide()
        } else { /* Ensure the fields are showing */
          $rows.show()
        }
      }

      /**
       * Check if the current PDF template selection uses the legacy Enable Advanced Templating option
       * and hide the Appearance tab altogether
       * @since 4.0
       */
      this.toggleAppearanceTab = function () {

        $('input[name="gfpdf_settings[advanced_template]"]').change(function () {
          if ($(this).val() == 'Yes') {
            $('#gfpdf-appearance-nav').hide()
          } else {
            $('#gfpdf-appearance-nav').show()
          }
        })

        $('input[name="gfpdf_settings[advanced_template]"]:checked').trigger('change')
      }

      /**
       * Initialises AJAX-loaded wp_editor TinyMCE containers for use
       * @param  Array editors  The DOM element IDs to parse
       * @param  Object settings The TinyMCE settings to use
       * @return void
       * @since  4.0
       */
      this.loadTinyMCEEditor = function (editors, settings) {

        if (settings != null) {
          /* Ensure appropriate settings defaults */
          settings.body_class = 'id post-type-post post-status-publish post-format-standard'
          settings.formats = {
            alignleft: [
              {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign: 'left'}},
              {selector: 'img,table,dl.wp-caption', classes: 'alignleft'}
            ],
            aligncenter: [
              {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign: 'center'}},
              {selector: 'img,table,dl.wp-caption', classes: 'aligncenter'}
            ],
            alignright: [
              {selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: {textAlign: 'right'}},
              {selector: 'img,table,dl.wp-caption', classes: 'alignright'}
            ],
            strikethrough: {inline: 'del'}
          }
        }

        /* Load our new editors */
        $.each(editors, function (index, fullId) {

          /* Setup out selector */
          settings.selector = '#' + fullId

          /* Initialise our editor */
          tinyMCE.init(settings)

          /* Add our editor to the DOM */
          tinyMCE.execCommand('mceAddEditor', false, fullId)

          /* Enable WP quick tags */
          if (typeof(QTags) == 'function') {
            QTags({'id': fullId})
            QTags._buttonsInit()

            /* remember last tab selected */
            if (typeof switchEditors.switchto === 'function') {
              switchEditors.switchto(jQuery('#wp-' + fullId + '-wrap').find('.wp-switch-editor.switch-' + ( getUserSetting('editor') == 'html' ? 'html' : 'tmce' ))[0])
            }
          }

        })
      }

      /**
       * Rich Media Uploader
       * JS Pulled straight from Easy Digital Download's admin-scripts.js
       * @return void
       * @since 4.0
       */
      this.doUploadListener = function () {
        // WP 3.5+ uploader
        var file_frame
        window.formfield = ''

        $('body').off('click', '.gfpdf_settings_upload_button').on('click', '.gfpdf_settings_upload_button', function (e) {
          e.preventDefault()

          var $button = $(this)
          window.formfield = $(this).parent().prev()

          /* If the media frame already exists, reopen it. */
          if (file_frame) {
            file_frame.open()
            return
          }

          /* Create the media frame. */
          file_frame = wp.media.frames.file_frame = wp.media({
            title: $button.data('uploader-title'),
            button: {
              text: $button.data('uploader-button-text')
            },
            multiple: false,
          })

          /* When a file is selected, run a callback. */
          file_frame.on('select', function () {
            var selection = file_frame.state().get('selection')
            selection.each(function (attachment, index) {
              attachment = attachment.toJSON()
              window.formfield.val(attachment.url).change()
            })
          })

          /* Finally, open the modal */
          file_frame.open()
        })
      }

      /**
       * Check if a Gravity PDF color picker field is present and initialise
       * @return void
       * @since 4.0
       */
      this.doColorPicker = function () {

        $('.gfpdf-color-picker').each(function () {
          $(this).wpColorPicker()
        })
      }

      /**
       * Remove any existing merge tags and reinitialise
       * @return void
       * @since 4.0
       */
      this.doMergetags = function () {

        /* Backwards compatibility support prior to Gravity Forms 2.3 */
        if (window.gfMergeTags && typeof form != 'undefined') {
          window.gfMergeTags = new gfMergeTagsObj(form)
          window.gfMergeTags.getTargetElement = this.resetGfMergeTags
        }

        /* Gravity Forms 2.3+ Merge tag support */
        if (!window.gfMergeTags && typeof form != 'undefined' && $('.merge-tag-support').length >= 0) {
          $('.merge-tag-support').each(function () {
            new gfMergeTagsObj(form, $(this))
          })
        }
      }

      /**
       * Escape any meta characters in the target element ID, as per the jQuery spec
       *
       * @param elem
       * @returns {*|HTMLElement}
       * @since 4.1
       */
      this.resetGfMergeTags = function (elem) {
        var $elem = $(elem)
        var selector = $elem.parents('span.all-merge-tags').data('targetElement')

        /* escape any meta-characters as per jQuery Spec http://api.jquery.com/category/selectors/ */
        selector = selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&")

        return $('#' + selector)
      }

      /**
       * Show / Hide our custom paper size as needed
       * @return void
       * @since 4.0
       */
      this.setupCustomPaperSize = function () {

        $('.gfpdf_paper_size').each(function () {
          var $customPaperSize = $(this).nextAll('.gfpdf_paper_size_other').first()
          var $paperSize = $(this).find('select')

          /* Add our change event */
          $paperSize.off('change').change(function () {
            if ($(this).val() === 'CUSTOM') {
              $customPaperSize.fadeIn()
            } else {
              $customPaperSize.fadeOut()
            }
          }).trigger('change')

        })

      }

      /**
       * Our &tab=(.+?) url param causes issues with the default GF navigation
       * @return void
       * @since 4.0
       */
      this.cleanupGFNavigation = function () {
        var $nav = $('#gform_tabs a')

        $nav.each(function () {
          var href = $(this).attr('href')
          var regex = new RegExp('&tab=[^&;]*', 'g')

          $(this).attr('href', href.replace(regex, ''))
        })
      }

      /**
       * Do an AJAX call to verify a user is protected
       * @return void
       * @since 4.0
       */
      this.runPDFAccessCheck = function () {
        var $status = $('#gfpdf-direct-pdf-protection-check')

        if ($status.length > 0) {
          /* Do our AJAX call */

          /* Add spinner */
          var $spinner = $('<img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" />')

          /* Add our spinner */
          $status.append($spinner)

          /* Set up ajax data */
          var data = {
            'action': 'gfpdf_has_pdf_protection',
            'nonce': $status.data('nonce'),
          }

          /* Do ajax call */
          this.ajax(data, function (response) {

            /* Remove our loading spinner */
            $spinner.remove()

            if (response === true) {
              /* enable our protected message */
              $status.find('#gfpdf-direct-pdf-check-protected').show()
            } else {
              /* enable our unprotected message */
              $status.find('#gfpdf-direct-pdf-check-unprotected').show()
            }
          })
        }
      }

      /**
       * Enable dynamic required fields on the Gravity Forms PDF Settings page
       * This function will highlight to the user which fields should be processed, and disable in-browser validation
       * @return void
       * @since 4.0
       */
      this.setupRequiredFields = function ($elm) {
        /* prevent in browser validation */
        $elm.attr('novalidate', 'novalidate')

        /* gf compatibility + disable automatic field validation */
        $elm.find('tr input[type="submit"]').click(function () {
          $elm.addClass('formSubmitted')
        })

        /* add the required star to make it easier for users */
        $elm.find('tr').each(function () {
          $(this).find(':input[required=""]:first, :input[required]:first').parents('tr').find('th').append('<span class="gfield_required">*</span>')
        })
      }

      /**
       * Because we are using the WordPress Settings API Gravity Forms tooltip support was lacking
       * This method fixes that issue
       * @return void
       * @since 4.0
       */
      this.showTooltips = function () {

        if (typeof gform_initialize_tooltips !== 'function') {
          return
        }

        $('.gf_hidden_tooltip').each(function () {
          $(this)
            .parent()
            .siblings('th:first')
            .append(' ')
            .append(
              self.get_tooltip($(this).html())
            )

          $(this).remove()
        })

        gform_initialize_tooltips()
      }

      /**
       * Set up 'chosen' select boxes
       * @return void
       * @since 4.0
       */
      this.setupSelectBoxes = function () {
        $('.gfpdf-chosen').each(function () {

          $(this).chosen({
            disable_search_threshold: 5,
            width: '100%',
          })
        })
      }

      /**
       * Controls the Advanced Options hide / show functionality
       * By default these fields are hidden, but are show automatically if an error occurs.
       * @return void
       * @since 4.0
       */
      this.setup_advanced_options = function () {
        var $advanced_options_toggle_container = $('.gfpdf-advanced-options')
        var $advanced_options_container = $advanced_options_toggle_container.prev()
        var $advanced_options = $advanced_options_toggle_container.find('a')

        /*
         * Show / Hide Advanced options
         */
        $advanced_options.click(function () {
          var click = this

          /* toggle our slider */
          $advanced_options_container.slideToggle(600, function () {
            /* Toggle our link text */
            var text = $(click).text()
            $(click).text(
              text == GFPDF.showAdvancedOptions ? GFPDF.hideAdvancedOptions : GFPDF.showAdvancedOptions
            )
          })

          return false
        })

        if ($('.gfpdf-advanced-options').prev().find('.gfield_error').length) {
          $advanced_options_container.show()
        }
      }

      /**
       * The general settings model method
       * This sets up and processes any of the JS that needs to be applied on the general settings tab
       * @return void
       * @since 4.0
       */
      this.generalSettings = function () {
        this.setupRequiredFields($('#pdfextended-settings > form'))

        var $table = $('#pdf-general-security')
        var $adminRestrictions = $table.find('input[name="gfpdf_settings[default_restrict_owner]"]')

        /*
         * Add change event to admin restrictions to show/hide dependant fields
         */
        $adminRestrictions.change(function () {

          if ($(this).is(':checked')) {
            if ($(this).val() === 'Yes') {
              /* hide user restrictions and logged out user timeout */
              $table.find('tr:nth-child(3)').hide()
            } else {
              /* hide user restrictions and logged out user timeout */
              $table.find('tr:nth-child(3)').show()
            }
          }
        }).trigger('change')

        /* setup advanced options */
        this.setup_advanced_options()
      }

      /**
       * The tools settings model method
       * This sets up and processes any of the JS that needs to be applied on the tools settings tab
       * @since 4.0
       */
      this.toolsSettings = function () {
        this.setupToolsTemplateInstallerDialog()
        this.setupToolsFontsDialog()
        this.setupToolsUninstallDialog()
      }

      /**
       * Handles the Template Installer Dialog Box
       * @return void
       * @since 4.0
       */
      this.setupToolsTemplateInstallerDialog = function () {

        var $copy = $('#gfpdf_settings\\[setup_templates\\]')
        /* escape braces */
        var $copyDialog = $('#setup-templates-confirm')

        /* Set up copy dialog */
        var copyButtons = [{
          text: GFPDF.continue,
          click: function () {
            /* submit form */
            $copy.unbind().click()
          }
        },
          {
            text: GFPDF.cancel,
            click: function () {
              /* cancel */
              $copyDialog.wpdialog('close')
            }
          }]

        if ($copyDialog.length) {
          this.wp_dialog($copyDialog, copyButtons, 500, 350)

          $copy.click(function () {
            /* Allow responsiveness */
            self.resizeDialogIfNeeded($copyDialog, 500, 350)

            $copyDialog.wpdialog('open')
            return false
          })
        }
      }

      /**
       * Handles the Fonts Dialog Box
       * @return void
       * @since 4.0
       */
      this.setupToolsFontsDialog = function () {
        var $font = $('#gfpdf_settings\\[manage_fonts\\]')
        /* escape braces */
        var $fontDialog = $('#manage-font-files')

        /* setup fonts dialog */
        this.wp_dialog($fontDialog, [], 500, 500)

        $font.click(function () {
          /* Allow responsiveness */
          self.resizeDialogIfNeeded($fontDialog, 500, 500)

          $fontDialog.wpdialog('open')
          return false
        })

        /* Check if our manage_fonts hash and open the dialog */
        if (window.location.hash) {
          if (window.location.hash == '#manage_fonts') {
            $font.click()
          }
        }
      }

      /**
       * Handles the Uninstall Dialog Box
       * @return void
       * @since 4.0
       */
      this.setupToolsUninstallDialog = function () {
        var $uninstall = $('#gfpdf-uninstall')
        var $uninstallDialog = $('#uninstall-confirm')

        /* Set up uninstall dialog */
        var uninstallButtons = [{
          text: GFPDF.uninstall,
          click: function () {
            /* submit form */
            $uninstall.parents('form').submit()
          }
        },
          {
            text: GFPDF.cancel,
            click: function () {
              /* cancel */
              $uninstallDialog.wpdialog('close')
            }
          }]

        this.wp_dialog($uninstallDialog, uninstallButtons, 500, 175)

        $uninstall.click(function () {
          /* Allow responsiveness */
          self.resizeDialogIfNeeded($uninstallDialog, 500, 175)

          $uninstallDialog.wpdialog('open')
          return false
        })
      }

      /**
       * Check the current browser width and height and set the dialog box size to fit
       * If the size is over 500 pixels (width or height) it will default to 500
       *
       * @param $dialog an object initialised with this.wp_dialog
       * @param Integer maxWidth The maximum width of the dialog box, if it will fit
       * @param Integer maxHeight The maximum height of the dialog box, if it will fit
       * @return void
       * @since 4.0
       */
      this.resizeDialogIfNeeded = function ($dialog, maxWidth, maxHeight) {
        var windowWidth = $(window).width()
        var windowHeight = $(window).height()

        var dialogWidth = (windowWidth < 500) ? windowWidth - 20 : maxWidth
        var dialogHeight = (windowHeight < 500) ? windowHeight - 50 : maxHeight

        $dialog.wpdialog('option', 'width', dialogWidth)
        $dialog.wpdialog('option', 'height', dialogHeight)
      }

      /**
       * Generate a WP Dialog box
       * @param  jQuery Object $elm        [description]
       * @param  Object buttonsList [description]
       * @param  Integer boxWidth    [description]
       * @param  Integer boxHeight   [description]
       * @return void
       * @since 4.0
       */
      this.wp_dialog = function ($elm, buttonsList, boxWidth, boxHeight) {
        $elm.wpdialog({
          autoOpen: false,
          resizable: false,
          draggable: false,
          width: boxWidth,
          height: boxHeight,
          modal: true,
          dialogClass: 'wp-dialog',
          zIndex: 300000,
          buttons: buttonsList,
          open: function () {
            $(this).siblings('.ui-dialog-buttonpane').find('button:eq(1)').focus()

            $('.ui-widget-overlay').bind('click', function () {
              $elm.wpdialog('close')
            })
          }
        })
      }

      /**
       * Create the tooltip HTML
       * @param  String html The tooltip message
       * @return String
       * @since 4.0
       */
      this.get_tooltip = function (html) {
        var $a = $('<a>')
        var $i = $('<i class="fa fa-question-circle">')

        $a.append($i)
        $a.addClass('gf_tooltip tooltip')
        $a.click(function () {
          return false
        })

        $a.attr('title', html)

        return $a
      }

      /**
       * An AJAX Wrapper function we can use to ajaxify our plugin
       * @param post Object an object of data to submit to our ajax endpoint. This MUST include an 'nonce' and an 'action'
       * @param successCallback a callback function
       * @return void
       * @since 4.0
       */
      this.ajax = function (post, successCallback) {
        $.ajax({
          type: "post",
          dataType: "json",
          url: GFPDF.ajaxUrl,
          data: post,
          success: successCallback,
          error: this.ajax_error,
        })
      }

      /**
       * Log the error to the console
       * @return void
       * @since 4.0
       */
      this.ajax_error = function (jqXHR, textStatus, errorThrown) {
        console.log(textStatus)
        console.log(errorThrown)
      }

      /**
       * Display a message or error to the user with an appropriate timeout
       * @param  String msg     The message to be displayed
       * @param  Integer timeout How long to show the message
       * @param  Boolean error   Whether to show an error (true) or a message (false or undefined)
       * @return void
       * @since 4.0
       */
      this.show_message = function (msg, timeout, error) {
        timeout = typeof timeout !== 'undefined' ? timeout : 4500
        error = typeof error !== 'undefined' ? error : false

        var $elm = $('<div id="message">').html('<p>' + msg + '</p>')

        if (error === true) {
          $elm.addClass('error')
        } else {
          $elm.addClass('updated')
        }

        $('.wrap > h2').after($elm)

        setTimeout(function () {
          $elm.slideUp()
        }, timeout)

      }

      /**
       * Update the URL parameter
       * @param String    The URL to parse
       * @param String    The URL parameter to want to update
       * @param String    The replacement string for the URL parameter
       * @return String   The processed URL
       * @since 4.0
       * @link http://stackoverflow.com/a/10997390/11236
       */
      this.updateURLParameter = function (url, param, paramVal) {
        var newAdditionalURL = ""
        var tempArray = url.split("?")
        var baseURL = tempArray[0]
        var additionalURL = tempArray[1]
        var temp = ""
        if (additionalURL) {
          tempArray = additionalURL.split("&")
          for (i = 0; i < tempArray.length; i++) {
            if (tempArray[i].split('=')[0] != param) {
              newAdditionalURL += temp + tempArray[i]
              temp = "&"
            }
          }
        }

        var rows_txt = temp + "" + param + "=" + paramVal
        return baseURL + "?" + newAdditionalURL + rows_txt
      }
    }

    var pdf = new GravityPDF()
    pdf.init()

  })
})(jQuery)
