import $ from 'jquery'
import templateBootstrap from './bootstrap/templateBootstrap'
import { fontManagerBootstrap } from './bootstrap/fontManagerBootstrap'
import coreFontBootstrap from './bootstrap/coreFontBootstrap'
import helpBootstrap from './bootstrap/helpBootstrap'
import addEditButton from './utilities/PdfSettings/addEditButton'
import autoSelectShortcode from './utilities/PdfList/autoSelectShortcode'
import checkFormUnsavedChanges from './utilities/PdfSettings/checkFormUnsavedChanges'
import disableOnbeforeunload from './utilities/PdfSettings/disableOnbeforeunload'
import '../../scss/gfpdf-styles.scss'

/**
 * JS Entry point for WebPack
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
    const templateId = '#gfpdf_settings\\[template\\], #gfpdf_settings\\[default_template\\]'
    const $templateField = $(templateId)

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
  if ($('#gpdf-search').length) {
    helpBootstrap()
  }

  const fmGeneralSettingsTab = document.querySelector('#gfpdf-settings-field-wrapper-default_font select')
  const fmToolsTab = document.querySelector('#gfpdf-settings-field-wrapper-manage_fonts')
  const fmPdfSettings = document.querySelector('#gfpdf-settings-field-wrapper-font select')
  const pdfSettingsForm = document.querySelector('form#gfpdf_pdf_form')
  const pdfSettingFieldSets = document.querySelectorAll('fieldset.gform-settings-panel--full')
  const gfPdfListForm = document.querySelector('form#gfpdf_list_form')
  const gPdfTabsForm = document.querySelector('form.gform_settings_form')
  const currentUrl = window.location.href

  /* Initialize font manager under general settings tab */
  if (fmGeneralSettingsTab !== null) {
    fontManagerBootstrap(fmGeneralSettingsTab)
  }

  /* Initialize font manager under tools tab  */
  if (fmToolsTab !== null) {
    fontManagerBootstrap(fmToolsTab, '-prevent-button-reset')
  }

  /* Initialize font manager under PDF settings */
  if (fmPdfSettings !== null) {
    fontManagerBootstrap(fmPdfSettings)
  }

  /* Initialize additional add/update buttons on PDF setting panels */
  if (pdfSettingsForm && pdfSettingFieldSets.length === 4) {
    addEditButton(pdfSettingFieldSets, pdfSettingsForm)
  }

  /* Enable shortcode field click and auto select feature */
  if (gfPdfListForm !== null) {
    autoSelectShortcode(gfPdfListForm)
  }

  /* Initialize form unsaved changes checker on General settings tab */
  if (currentUrl.includes('tab=general') || currentUrl.includes('subview=PDF#/')) {
    checkFormUnsavedChanges(gPdfTabsForm)
    disableOnbeforeunload(gPdfTabsForm)
  }

  /* Initialize form unsaved changes checker on License tab */
  if (currentUrl.includes('tab=license')) {
    checkFormUnsavedChanges(gPdfTabsForm)
    disableOnbeforeunload(gPdfTabsForm)
  }

  /* Initialize form unsaved changes checker on New / Existing PDF settings */
  if (currentUrl.includes('&pid=')) {
    checkFormUnsavedChanges(pdfSettingsForm)
    disableOnbeforeunload(pdfSettingsForm)
  }
})
