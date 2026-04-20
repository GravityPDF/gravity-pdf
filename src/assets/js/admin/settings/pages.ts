const $ = jQuery;

class Pages {
	/**
	 * Get if on the global PDF settings pages
	 *
	 * @return { boolean } true if on global PDF settings page
	 *
	 * @since 4.0
	 */
	isSettings(): boolean {
		return $('#pdfextended-settings').length > 0;
	}

	/**
	 * Check if on the individual PDF form settings pages
	 *
	 * @return { boolean } true if on individual PDF form settings page
	 *
	 * @since 4.0
	 */
	isFormSettings(): boolean {
		return $('.gforms_edit_form').length > 0;
	}

	/**
	 * See if we are on the form settings list page
	 *
	 * @return { boolean } true if on form settings list page
	 *
	 * @since 4.0
	 */
	isFormSettingsList(): boolean {
		return $('#gfpdf_list_form').length > 0;
	}

	/**
	 * See if we are on the form settings edit page
	 *
	 * @return { boolean } true if on form settings edit page
	 *
	 * @since 4.0
	 */
	isFormSettingsEdit(): boolean {
		return $('#gfpdf_pdf_form').length > 0;
	}
}

export const pages = new Pages();
