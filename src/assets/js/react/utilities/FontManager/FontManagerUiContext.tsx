/* Dependencies */
import { createContext, useContext } from '@wordpress/element';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * Tiny shared context so the bootstrap's outer `Modal` can read the inner
 * modal's `dirty` and `confirmOpen` flags when deciding whether Esc should
 * close the modal. The values are mutable via refs to avoid re-rendering
 * the bootstrap on every keystroke inside the inner modal.
 */
export interface FontManagerUiState {
	/** True while a confirm dialog (delete / replace-files) is open. */
	confirmOpen: boolean;
	/** True while the editing slice has unsaved changes. */
	dirty: boolean;
}

export const FontManagerUiContext = createContext<{
	getState: () => FontManagerUiState;
	setState: (next: Partial<FontManagerUiState>) => void;
	/**
	 * Run `continueAction` immediately when the editing slice is clean,
	 * otherwise surface a "Discard unsaved changes?" confirm dialog
	 * (handled by the bootstrap) and run the action only on confirm.
	 */
	requestDiscard: (continueAction: () => void) => void;
}>({
	getState: () => ({ confirmOpen: false, dirty: false }),
	setState: () => {
		/* default no-op for tests that render the modal in isolation */
	},
	requestDiscard: (continueAction) => continueAction(),
});

export function useFontManagerUi() {
	return useContext(FontManagerUiContext);
}
