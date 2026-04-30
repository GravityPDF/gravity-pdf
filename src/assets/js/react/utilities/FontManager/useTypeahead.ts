/* Dependencies */
import { useRef } from '@wordpress/element';
import type { KeyboardEvent } from 'react';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface ListItem {
	id: string;
	name: string;
}

interface UseTypeaheadResult {
	handleKeyDown: (event: KeyboardEvent) => string | null;
}

const RESET_MS = 500;

/**
 * Listbox first-letter / type-ahead navigation. Returns a handler that
 * accepts a keyboard event and returns the id of the next matching list
 * item (or null if no match). Multi-character input within 500ms is
 * concatenated so typing "mo" jumps to "Montserrat" instead of cycling
 * "M" then "O". Repeated single-char presses cycle through matches.
 *
 * @param items   Ordered list of items to navigate over.
 * @param current Currently active item id (or empty string).
 */
export function useTypeahead(
	items: ListItem[],
	current: string
): UseTypeaheadResult {
	const bufferRef = useRef('');
	const lastKeyTimeRef = useRef(0);

	const handleKeyDown = (event: KeyboardEvent): string | null => {
		const key = event.key;
		if (key.length !== 1 || !/[\p{L}\p{N}]/u.test(key)) {
			return null;
		}
		if (event.metaKey || event.ctrlKey || event.altKey) {
			return null;
		}

		const now = Date.now();
		const elapsed = now - lastKeyTimeRef.current;
		lastKeyTimeRef.current = now;

		const isSingleCharRepeat =
			elapsed < RESET_MS &&
			bufferRef.current.length === 1 &&
			bufferRef.current.toLowerCase() === key.toLowerCase();

		if (elapsed > RESET_MS) {
			bufferRef.current = key;
		} else if (isSingleCharRepeat) {
			/* Cycle: keep buffer at one char so we move to the next match */
			bufferRef.current = key;
		} else {
			bufferRef.current += key;
		}

		if (items.length === 0) {
			return null;
		}

		const needle = bufferRef.current.toLowerCase();
		const startIndex = Math.max(
			0,
			items.findIndex((it) => it.id === current)
		);

		/* On a cycle, start AFTER the current. On multi-char buffer or first
		   press, start AT the current (so typing "mo" lands on the first
		   "Mo*", not the next one after the current). */
		const offset = isSingleCharRepeat ? 1 : 0;

		for (let i = 0; i < items.length; i++) {
			const idx = (startIndex + offset + i) % items.length;
			const candidate = items[idx];
			if (candidate.name.toLowerCase().startsWith(needle)) {
				return candidate.id;
			}
		}

		return null;
	};

	return { handleKeyDown };
}
