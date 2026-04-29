const $ = jQuery;

declare global {
	interface Window {
		formfield: JQuery | string;
	}
}

/* eslint-disable no-var */
declare const wp: {
	media: (options?: object) => {
		on: (event: string, cb: () => void) => void;
		open: () => void;
		state: () => {
			get: (key: string) => {
				toJSON: () => { url: string; id: number };
			};
		};
	};
};
/* eslint-enable no-var */

/**
 * Rich Media Uploader
 * JS Pulled straight from Easy Digital Download's admin-scripts.js
 *
 * @since 4.0
 */
export function doUploadListener(): void {
	window.formfield = '';

	$('body')
		.off('click', '.gfpdf_settings_upload_button')
		.on(
			'click',
			'.gfpdf_settings_upload_button',
			function (this: HTMLElement, e: JQuery.ClickEvent) {
				e.preventDefault();

				window.formfield = $(this).prev();

				/* If the media frame already exists, reopen it. */
				if (window.fileFrame) {
					window.fileFrame.open();
					return;
				}

				const $button = $(this);

				/* Create the media frame. */
				const frame = wp.media({
					title: $button.data('uploader-title') as string,
					button: {
						text: $button.data('uploader-button-text') as string,
					},
					multiple: false,
				});
				window.fileFrame = frame;

				/* When a file is selected, run a callback. */
				frame.on('select', function () {
					const selection = frame
						.state()
						.get('selection') as unknown as {
						each: (
							cb: (attachment: {
								toJSON: () => { url: string; id: number };
							}) => void
						) => void;
					};
					selection.each(function (attachment: {
						toJSON: () => { url: string; id: number };
					}) {
						const data = attachment.toJSON();
						(window.formfield as JQuery)
							.val(data.url)
							.trigger('change');
					});
				});

				/* Finally, open the modal */
				frame.open();
			}
		);
}
