import $ from 'jquery'
import templateBootstrap from './bootstrap/templateBootstrap'

$(function () {

  'use strict'

  /* Initialise the Fancy Template Picker */
  if (GFPDF.templateList !== undefined) {

    /* Check if we should show the Fancy Template Picker */
    var templateId = (GFPDF.activeTemplate !== undefined) ?
      '#gfpdf_settings\\[template\\]' :
      '#gfpdf_settings\\[default_template\\]'

    var $templateField = $(templateId)

    /* Run this code if the element exists */
    if ($templateField.length > 0) {
        templateBootstrap($templateField)
    }
  }
})