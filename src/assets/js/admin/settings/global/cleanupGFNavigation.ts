const $ = jQuery;

/**
 * Our &tab=(.+?) url param causes issues with the default GF navigation
 *
 * @since 4.0
 */
export function cleanupGFNavigation(): void {
	const $nav = $('#gform_tabs a');

	$nav.each(function (this: HTMLElement) {
		const href = $(this).attr('href');
		const regex = new RegExp('&tab=[^&;]*', 'g'); // eslint-disable-line

		if (href !== undefined) {
			$(this).attr('href', href.replace(regex, ''));
		}
	});
}
