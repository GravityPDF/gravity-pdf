import $ from 'jquery'
import templateBootstrap from './bootstrap/templateBootstrap'
import { fontManagerBootstrap } from './bootstrap/fontManagerBootstrap'
import coreFontBootstrap from './bootstrap/coreFontBootstrap'
import helpBootstrap from './bootstrap/helpBootstrap'
import '../../scss/gfpdf-styles.scss'

/**
 * JS Entry point for WebPack
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * Our main entry point for our modern unit-tested JS
 * This file gets run through Webpack to built it into valid ES5
 *
 * As we convert more JS to ES6 we'll likely load it from this file (unless we decide to make each feature modular)
 *
 * @since 4.1
 */
$(function () {
  'use strict'

  __webpack_public_path__ = GFPDF.pluginUrl + 'dist/' // eslint-disable-line

  /* Initialize the Fancy Template Picker */
  if (GFPDF.templateList !== undefined) {
    // To add to window
    if (!window.Promise) {
      window.Promise = Promise
    }

    /* Check if we should show the Fancy Template Picker */
    var templateId = '#gfpdf_settings\\[template\\], #gfpdf_settings\\[default_template\\]'
    var $templateField = $(templateId)

    /* Run this code if the element exists */
    if ($templateField.length > 0) {
      templateBootstrap($templateField)
    }
  }

  /* Initialize the Core Font downloader */
  if ($('#gfpdf-button-wrapper-install_core_fonts').length) {
    coreFontBootstrap()
  }

  /* Initialize the Search Bar for Help Tab */
  if ($('#search-knowledgebase').length) {
    helpBootstrap()
  }

  const FmGeneralSettingsTab = document.querySelector('#gfpdf-settings-field-wrapper-default_font')
  const FmToolsTab = document.querySelector('#gfpdf-settings-field-wrapper-manage_fonts')
  const FmPdfSettings = document.querySelector('#gfpdf-settings-field-wrapper-font')

  /* Initialize font manager under general settings tab */
  if (FmGeneralSettingsTab !== null) {
    fontManagerBootstrap(FmGeneralSettingsTab)
  }

  /* Initialize font manager under tools tab  */
  if (FmToolsTab !== null) {
    fontManagerBootstrap(FmToolsTab)
  }

  /* Initialize font manager under PDF settings */
  if (FmPdfSettings !== null) {
    fontManagerBootstrap(FmPdfSettings)
  }
})
