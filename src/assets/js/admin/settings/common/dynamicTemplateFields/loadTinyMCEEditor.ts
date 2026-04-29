/* eslint-disable no-var */
declare const tinyMCE: {
	init: (settings: object) => void;
	execCommand: (cmd: string, ui?: boolean, value?: unknown) => boolean;
};
declare const QTags:
	| (((opts: { id: string }) => void) & { _buttonsInit: () => void })
	| undefined;
declare const switchEditors:
	| { go: (id: string, mode: string) => void }
	| undefined;
declare function getUserSetting(name: string, fallback?: string): string;
/* eslint-enable no-var */

/**
 * Initialises AJAX-loaded wp_editor TinyMCE containers for use
 * @param {Array.<string>}          editors  The DOM element IDs to parse
 * @param {Record<string, unknown>} settings The TinyMCE settings to use
 *
 * @since  4.0
 */
export function loadTinyMCEEditor(
	editors: string[],
	settings: Record<string, unknown>
): void {
	if (settings) {
		/* Ensure appropriate settings defaults */
		settings.body_class =
			'id post-type-post post-status-publish post-format-standard';
		settings.formats = {
			alignleft: [
				{
					selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li',
					styles: { textAlign: 'left' },
				},
				{ selector: 'img,table,dl.wp-caption', classes: 'alignleft' },
			],
			aligncenter: [
				{
					selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li',
					styles: { textAlign: 'center' },
				},
				{ selector: 'img,table,dl.wp-caption', classes: 'aligncenter' },
			],
			alignright: [
				{
					selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li',
					styles: { textAlign: 'right' },
				},
				{ selector: 'img,table,dl.wp-caption', classes: 'alignright' },
			],
			strikethrough: { inline: 'del' },
		};
		settings.content_style =
			'body#tinymce { max-width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;}';
	}

	/* Load our new editors */
	editors.forEach(function (fullId: string) {
		/* Setup out selector */
		settings.selector = '#' + fullId;

		/* Initialise our editor */
		tinyMCE.init(settings);

		/* Add our editor to the DOM */
		tinyMCE.execCommand('mceAddEditor', false, fullId);

		/* Enable WP quick tags */
		if (typeof QTags === 'function') {
			QTags({ id: fullId });
			QTags._buttonsInit();

			/* remember last tab selected */
			if (
				typeof switchEditors !== 'undefined' &&
				typeof switchEditors.go === 'function'
			) {
				switchEditors.go(
					fullId,
					getUserSetting('editor') === 'html' ? 'html' : 'tmce'
				);
			}
		}
	});
}
