import $ from 'jquery';

class Pages {
	/**
	 * Get if on the global PDF settings pages
	 *
	 * @return { number } global PDF settings length
	 *
	 * @since 4.0
	 */
	isSettings() {
		return $('#pdfextended-settings').length;
	}

	/**
	 * Check if on the individual PDF form settings pages
	 *
	 * @return { number } individual PDF form settings length
	 *
	 * @since 4.0
	 */
	isFormSettings() {
		return $('.gforms_edit_form').length;
	}

	/**
	 * See if we are on the form settings list page
	 *
	 * @return { number } form list settings length
	 *
	 * @since 4.0
	 */
	isFormSettingsList() {
		return $('#gfpdf_list_form').length;
	}

	/**
	 * See if we are on the form settings edit page
	 *
	 * @return { number } PDF form settings length
	 *
	 * @since 4.0
	 */
	isFormSettingsEdit() {
		return $('#gfpdf_pdf_form').length;
	}
}

export const pages = new Pages();
