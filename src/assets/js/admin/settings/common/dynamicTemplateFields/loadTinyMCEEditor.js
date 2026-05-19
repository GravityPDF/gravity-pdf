/**
 * Initialises AJAX-loaded wp_editor TinyMCE containers for use
 * @param { Array<string> }           editors  The DOM element IDs to parse
 * @param { Record<string, unknown> } settings The TinyMCE settings to use
 *
 * @since  4.0
 */
export function loadTinyMCEEditor(editors, settings) {
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

	const lastEditorTab = getUserSetting('editor') === 'html' ? 'html' : 'tmce';

	/* Load our new editors */
	editors.forEach(function (fullId) {
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
			restoreLastEditorTab(fullId, lastEditorTab);
		}
	});
}

/**
 * Restore the user's last-selected editor tab on a freshly mounted wp_editor.
 * Deferred via editor.on('init') — switchEditors.go throws if the iframe isn't built.
 *
 * @param {string} fullId
 * @param {string} mode   Either 'html' or 'tmce'.
 */
function restoreLastEditorTab(fullId, mode) {
	if (
		typeof switchEditors === 'undefined' ||
		typeof switchEditors.go !== 'function'
	) {
		return;
	}

	const apply = function () {
		try {
			/* Clear TinyMCE's default-cursor range so switchEditors.go's findBookmarkedPosition
			 * short-circuits — otherwise it schedules a textArea.focus() that scrolls the page. */
			const editor = tinyMCE.get(fullId);
			const editorWindow =
				editor && typeof editor.getWin === 'function'
					? editor.getWin()
					: null;
			if (
				editorWindow &&
				typeof editorWindow.getSelection === 'function'
			) {
				const selection = editorWindow.getSelection();
				if (selection) {
					selection.removeAllRanges();
				}
			}
			switchEditors.go(fullId, mode);
		} catch (e) {
			/* Fall back silently to Visual mode if WP throws — the user can flip the tab manually. */
		}
	};

	const editor = tinyMCE.get(fullId);
	if (editor && editor.initialized) {
		apply();
	} else if (editor && typeof editor.on === 'function') {
		editor.on('init', apply);
	}
}
