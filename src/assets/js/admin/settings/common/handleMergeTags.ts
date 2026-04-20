const $ = jQuery;

/**
 * Wrap fields in a container so the merge tag selector displays correctly
 * @param {string | HTMLElement} selector
 * @since 6.14.2
 */
export function handleMergeTags(selector: string | HTMLElement = 'body'): void {
	/* Add better merge tag support */
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	$(selector as unknown as any)
		.find('.gform-settings-field')
		.each(function (this: HTMLElement) {
			$(this)
				.find('.merge-tag-support, .merge-tag-support + span')
				.wrapAll(
					'<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag"></div>'
				);

			$(this)
				.find('.all-merge-tags.textarea')
				.parent()
				.wrapAll(
					'<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag gfpdf-merge-tag-container"></div>'
				);
		});
}
