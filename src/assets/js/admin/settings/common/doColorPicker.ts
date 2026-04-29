const $ = jQuery;

/**
 * Check if a Gravity PDF color picker field is present and initialise
 *
 * @since 4.0
 */
export function doColorPicker(): void {
	$('.gfpdf-color-picker').each(function (this: HTMLElement) {
		$(this).wpColorPicker({
			width: 300,
		});
		$(this)
			.parents('.wp-picker-container')
			.find('.wp-color-result')
			.addClass('ed_button');
	});
}
