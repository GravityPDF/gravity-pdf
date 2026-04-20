const $ = jQuery;

/* eslint-disable no-var */
declare const tinyMCE: {
	get: (
		id: string
	) => { id: string; setContent: (content: string) => void } | null;
};
/* eslint-enable no-var */

/**
 * Add change event listeners on our toggle params and toggle the container
 *
 * @since 4.0
 */
export function setupToggledFields(): void {
	$('form')
		.off('change', '.gfpdf-input-toggle')
		.on('change', '.gfpdf-input-toggle', function (this: HTMLElement) {
			const $container = $(this).parent().next();

			/* Currently checked so hide out input and if cotains rich_text, textarea or input we will delete values */
			if ($(this).prop('checked')) {
				$container.slideDown('slow');
			} else {
				$container.slideUp('slow');

				/* Remove TinyMCE Content */
				$container.find('.wp-editor-area').each(function (
					this: HTMLElement
				) {
					const editor = tinyMCE.get($(this).attr('id') ?? '');

					if (editor !== null) {
						editor.setContent('');
					}
				});

				/* Remove textarea content */
				$container.find('textarea').each(function (this: HTMLElement) {
					$(this).val('');
				});
			}
		});
}
