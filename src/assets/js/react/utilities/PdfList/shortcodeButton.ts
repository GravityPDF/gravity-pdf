declare const ClipboardJS: any;

export default function shortcodeButton(): void {
	/* Fallback when clipboard not available, or clipboard error */
	if (!ClipboardJS.isSupported()) {
		fallback('.btn-shortcode');

		return;
	}

	const clipboard = new ClipboardJS('.btn-shortcode');
	clipboard.on('success', function (e: any) {
		const gpdf = new GPDFShortcodeButton(e.trigger);
		gpdf.buttonActive();
	});

	clipboard.on('error', function (e: any) {
		fallback(e.trigger);
		jQuery(e.trigger).trigger('click');
	});

	function fallback(selector: string | HTMLElement): void {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		(jQuery as any)(selector).on('click', function (this: HTMLElement) {
			jQuery(this).toggleClass('toggle');
			if (jQuery(this).hasClass('toggle')) {
				jQuery(this).next().find('input').focus();
			}
		});

		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		(jQuery as any)(selector)
			.next()
			.find('input')
			.on('click focus', function (this: HTMLElement) {
				jQuery(this).select();
			});
	}

	class GPDFShortcodeButton {
		element: JQuery;

		constructor(element: HTMLElement) {
			this.element = jQuery(element);
		}

		buttonDefault(): void {
			if (this.element.hasClass('gf_2_5')) {
				this.element.removeClass('btn-success');
				this.element.text(this.element.text());
			} else {
				this.element.removeClass(
					'gform-embed-form__shortcode-trigger--copied'
				);
			}
		}

		buttonActive(): void {
			if (this.element.hasClass('gf_2_5')) {
				this.element.addClass('btn-success');
				this.element.text(this.element.data('selectedText'));
			} else {
				this.element.addClass(
					'gform-embed-form__shortcode-trigger--copied'
				);
			}
			/* Show "Copied!" feedback for 3s, then reset the button label */
			setTimeout(() => this.buttonDefault(), 3000);
		}
	}
}
