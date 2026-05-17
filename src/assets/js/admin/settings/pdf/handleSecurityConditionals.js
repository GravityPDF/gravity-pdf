import $ from 'jquery';

/**
 * Handles our DOM security conditional logic based on the user selection
 *
 * @since 4.0
 */
export function handleSecurityConditionals() {
	/* Get the appropriate elements for use */
	const $secTable = $('#gfpdf-fieldset-gfpdf_form_settings_advanced');
	const $pdfSecurity = $secTable.find(
		'input[name="gfpdf_settings[security]"]'
	);
	const $format = $secTable.find('input[name="gfpdf_settings[format]"]');
	const $securityQuestion = $secTable.find(
		'#gfpdf-settings-field-wrapper-security'
	);
	const $securityFields = $secTable.find(
		'#gfpdf-settings-field-wrapper-password,#gfpdf-settings-field-wrapper-privileges,#gfpdf-settings-field-wrapper-master_password:not(.gfpdf-hidden)'
	);

	/* Add change event to admin restrictions to show/hide dependant fields */
	$pdfSecurity
		.on('change', function () {
			/* Get the format dependency */
			const format = $format.filter(':checked').val();

			if ($(this).val() === 'No' || format !== 'Standard') {
				/* hide security password / privileges */
				$securityFields.hide();
			} else {
				/* Show/hide security password / privileges fields under 'Enable PDF Security' */

				// eslint-disable-next-line no-unused-expressions
				$(this).is(':checked')
					? $securityFields.show()
					: $securityFields.hide();
			}

			if (format !== 'Standard') {
				$securityQuestion.hide();
			} else {
				$securityQuestion.show();
			}
		})
		.trigger('change');

	/* The format field effects the security field. When it changes it triggers the security field as changed */
	$format
		.on('change', function () {
			if ($(this).is(':checked')) {
				$pdfSecurity.trigger('change');
			}
		})
		.trigger('change');
}
