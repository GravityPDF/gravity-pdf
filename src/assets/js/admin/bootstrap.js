import $ from 'jquery'
import { initialiseSettings } from './settings/initialiseSettings'

/**
 * Gravity PDF Settings JS Logic
 * Dependancies: backbone, underscore, jquery
 * @since 4.0
 */

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
     * Process the correct settings area (the global PDF settings or individual form PDF settings)
     * Also set up any event listeners needed
     * @return void
     * @since 4.0
     */
    initialiseSettings.init()
  }

  GravityPDF()
})
