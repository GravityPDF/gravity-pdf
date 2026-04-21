/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

declare global {
	/* ── jQuery plugin augmentations ─────────────────────────────────────── */
	interface JQuery {
		wpColorPicker(options?: object): JQuery;
		toJSON(): string;
	}

	interface JQueryStatic {
		toJSON(value: unknown): string;
	}

	/* ── WordPress globals ───────────────────────────────────────────────── */
	const wp: {
		media(options?: object): {
			on(event: string, cb: () => void): void;
			open(): void;
			state(): {
				get(key: string): {
					toJSON(): { url: string; id: number };
				};
			};
		};
	};

	function getUserSetting(name: string, fallback?: string): string;

	const switchEditors:
		| { switchto(el: HTMLElement, mode: string): void }
		| undefined;

	const QTags:
		| {
				addButton(
					id: string,
					label: string,
					openTag: string,
					closeTag: string
				): void;
		  }
		| undefined;

	function gform_initialize_tooltips(): void;

	/* ── TinyMCE ─────────────────────────────────────────────────────────── */
	const tinyMCE: {
		init(settings: object): void;
		execCommand(cmd: string, ui?: boolean, value?: unknown): boolean;
		triggerSave(): void;
		editors: Record<string, { id: string }>;
		remove(selector?: string): void;
		get(id: string): { id: string } | null;
	};

	/* ── Gravity Forms ───────────────────────────────────────────────────── */
	const gform: {
		addFilter(
			hook: string,
			callback: (...args: unknown[]) => unknown
		): void;
	};

	const gf_vars: Record<string, unknown> | undefined;

	class ConditionalLogic {
		constructor(data?: unknown);
		rules: Array<{ fieldId: string | number; [key: string]: unknown }>;
		[key: string]: unknown;
	}
	function GetFirstRuleField(): string;
	function GetRuleValuesDropDown(
		field: unknown,
		objectType: unknown,
		ruleIndex: unknown,
		selectedValue: string,
		inputName?: string
	): string;
	function ToggleConditionalLogic(init: boolean, logicObject: unknown): void;

	/* ── Plugin window properties set by PHP ────────────────────────────── */
	interface Window {
		gfpdf_current_pdf: Record<string, unknown> | undefined;
		gfpdf_extra_conditional_logic_options:
			| Record<string, unknown>
			| undefined;
		fileFrame: ReturnType<typeof wp.media> | undefined;
	}
}

/* ── Admin-only GFPDF properties ─────────────────────────────────────── */
declare module '../../react/types/global' {
	interface GFPDFGlobal {
		/* List page strings */
		letsGoCreateOne: string;
		thisFormHasNoPdfs: string;
		pdfDeleteWarning: string;

		/* Conditional logic strings */
		conditionalText: string;
		enable: string;
		disable: string;
	}
}

export {};
