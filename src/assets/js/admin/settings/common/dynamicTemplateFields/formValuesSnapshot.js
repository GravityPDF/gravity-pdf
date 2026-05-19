import $ from 'jquery';

export const TEMPLATE_SECTION_SELECTOR =
	'#gfpdf-fieldset-gfpdf_form_settings_template';
export const TEMPLATE_DROPDOWN_SELECTOR = '#gfpdf_settings\\[template\\]';

/**
 * Capture every recoverable user input inside $container. Returns a flat
 * array of entries discriminated by `kind`:
 *
 *   { kind: 'input',  name, value, checked? }
 *   { kind: 'toggle', controls, checked }
 *
 * For TinyMCE-backed textareas the editor's live content is used when the
 * editor is in Visual mode (`editor.hidden === false`). In Code mode the
 * textarea itself holds the live edits, so the editor body is ignored.
 *
 * `.gfpdf-input-toggle` controls (the First Page Header/Footer toggles)
 * have no `name` attribute, so they're keyed by the name of the textarea
 * inside the container they show/hide — a stable identity across the
 * AJAX-driven HTML swap.
 *
 * @param {jQuery} $container
 * @return {Array<object>} Snapshot entries keyed by name/controls.
 */
export function snapshotFormValues($container) {
	const entries = [];

	$container
		.find(':input')
		.not(TEMPLATE_DROPDOWN_SELECTOR)
		.each(function () {
			const $el = $(this);
			const name = $el.attr('name');
			if (!name || $el.is(':button, :submit, :reset, [type=file]')) {
				return;
			}

			if ($el.is(':checkbox') || $el.is(':radio')) {
				entries.push({
					kind: 'input',
					name,
					value: $el.val(),
					checked: $el.prop('checked'),
				});
				return;
			}

			const id = $el.attr('id');
			if ($el.is('textarea') && id && typeof tinyMCE !== 'undefined') {
				const editor = tinyMCE.get(id);
				if (editor && !editor.hidden) {
					entries.push({
						kind: 'input',
						name,
						value: editor.getContent(),
					});
					return;
				}
			}

			entries.push({ kind: 'input', name, value: $el.val() });
		});

	$container.find('.gfpdf-input-toggle').each(function () {
		const $toggle = $(this);
		const controlsName = $toggle
			.parent()
			.next()
			.find('textarea')
			.first()
			.attr('name');
		if (!controlsName) {
			return;
		}
		entries.push({
			kind: 'toggle',
			controls: controlsName,
			checked: $toggle.prop('checked'),
		});
	});

	return entries;
}

/**
 * Re-apply a snapshot captured by snapshotFormValues() to whatever fields
 * still exist after the template section's HTML was swapped. Snapshot
 * entries with no match in the new HTML are silently dropped.
 *
 * Restoring a checkbox/radio (or toggle) fires `change` so dependent UI
 * driven by `setupToggledFields` slides its conditional panel into the
 * right state.
 *
 * @param {jQuery}        $container
 * @param {Array<object>} snapshot   see snapshotFormValues
 */
export function restoreFormValues($container, snapshot) {
	snapshot.forEach(function (item) {
		if (item.kind === 'toggle') {
			const $toggle = $container
				.find('.gfpdf-input-toggle')
				.filter(function () {
					return (
						$(this)
							.parent()
							.next()
							.find('textarea')
							.first()
							.attr('name') === item.controls
					);
				});
			if (!$toggle.length || $toggle.prop('checked') === item.checked) {
				return;
			}
			$toggle.prop('checked', item.checked).trigger('change');
			return;
		}

		const $matches = $container.find(':input[name="' + item.name + '"]');
		if (!$matches.length) {
			return;
		}

		if (typeof item.checked === 'boolean') {
			const $target = $matches.filter(function () {
				return $(this).val() === item.value;
			});
			if (!$target.length || $target.prop('checked') === item.checked) {
				return;
			}
			$target.prop('checked', item.checked).trigger('change');
			return;
		}

		$matches.first().val(item.value);
	});
}
