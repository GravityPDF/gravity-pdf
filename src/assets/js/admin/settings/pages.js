import $ from 'jquery'

class Pages {
  /**
   * Get if on the global PDF settings pages
   * @return Integer
   * @since 4.0
   */
  isSettings () {
    return $('#gfpdf-global-settings-navigation').length
  }

  /**
   * Check if on the individual PDF form settings pages
   * @return Integer
   * @since 4.0
   */
  isFormSettings () {
    return $('#tab_pdf').length
  }

  /**
   * See if we are on the form settings list page
   * @return Integer
   * @since 4.0
   */
  isFormSettingsList () {
    return $('#gfpdf_list_form').length
  }

  /**
   * See if we are on the form settings edit page
   * @return Integer
   * @since 4.0
   */
  isFormSettingsEdit () {
    return $('#gfpdf_pdf_form').length
  }
}

export const pages = new Pages()
