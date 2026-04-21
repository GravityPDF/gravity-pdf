const $ = jQuery;

export function spinner(classname: string): JQuery {
	const $spinner = $(
		'<img alt=' +
			GFPDF.spinnerAlt +
			' src=' +
			GFPDF.spinnerUrl +
			' class=' +
			classname +
			' />'
	);
	return $spinner;
}
