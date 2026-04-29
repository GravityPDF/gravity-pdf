const $ = jQuery;
import { __ } from '@wordpress/i18n';

export function spinner(classname: string): JQuery {
	const $spinner = $(
		'<img alt=' +
			__('Loading…', 'gravity-pdf') +
			' src=' +
			GFPDF.spinnerUrl +
			' class=' +
			classname +
			' />'
	);
	return $spinner;
}
