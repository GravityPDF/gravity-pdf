const $ = jQuery;

/**
 * Show / Hide our custom paper size as needed
 *
 * @since 4.0
 */
export function setupCustomPaperSize(): void {
	$('.gfpdf_paper_size').each(function (this: HTMLElement) {
		const $customPaperSize = $(this)
			.nextAll('.gfpdf_paper_size_other')
			.first();
		const $paperSize = $(this).find('select');

		/* Add our change event */
		$paperSize
			.off('change')
			.on('change', function (this: HTMLElement) {
				if (($(this).val() as string) === 'CUSTOM') {
					$customPaperSize.fadeIn();
				} else {
					$customPaperSize.fadeOut();
				}
			})
			.trigger('change');
	});
}
