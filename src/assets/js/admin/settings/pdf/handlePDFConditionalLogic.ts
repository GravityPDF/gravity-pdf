const $ = jQuery;

/**
 * Add GF JS filter to change the conditional logic object type to our PDF
 *
 * @since 4.0
 */
export function handlePDFConditionalLogic(): void {
	gform.addFilter('gform_conditional_object', function (obj, objectType) {
		if (objectType === 'gfpdf') {
			obj = window.gfpdf_current_pdf;

			const current = obj as Record<string, unknown>;

			/* Manually setup new conditional logic object, with fallback to entry metadata if no available fields present */
			if (
				!current.conditionalLogic ||
				(current.conditionalLogic as unknown[]).length === 0
			) {
				const logic = new ConditionalLogic();
				current.conditionalLogic = logic;
				logic.rules[0].fieldId = GetFirstRuleField();
				if ((logic.rules[0].fieldId as unknown) === 0) {
					logic.rules[0].fieldId = 'id';
				}
			}
		}
		return obj;
	});

	/* Add support for entry meta */
	const entryOptions = window.gfpdf_extra_conditional_logic_options;
	gform.addFilter('gform_conditional_logic_fields', function (options) {
		const opts = options as Array<{ value: unknown; label: string }>;
		if (entryOptions !== undefined) {
			for (const property in entryOptions) {
				// Entry meta are already added in Notifications and Confirmations conditional logic but not in feeds.
				// Let's just make sure that none of our entry meta options have been previously added.
				if (
					Object.prototype.hasOwnProperty.call(
						entryOptions,
						property
					) &&
					!opts.find(
						(opt) =>
							opt.value ===
							(entryOptions[property] as Record<string, unknown>)
								.value
					)
				) {
					opts.push({
						label: String(
							(entryOptions[property] as Record<string, unknown>)
								.label ?? ''
						),
						value: (
							entryOptions[property] as Record<string, unknown>
						).value,
					});
				}
			}
		}
		return opts;
	});

	gform.addFilter(
		'gform_conditional_logic_operators',
		function (operators, objectType, fieldId) {
			if (
				entryOptions !== undefined &&
				Object.prototype.hasOwnProperty.call(
					entryOptions,
					fieldId as string
				)
			) {
				operators = (
					entryOptions[fieldId as string] as Record<string, unknown>
				).operators;
			}
			return operators;
		}
	);

	gform.addFilter(
		'gform_conditional_logic_values_input',
		function (str, objectType, ruleIndex, selectedFieldId, selectedValue) {
			if (
				entryOptions !== undefined &&
				Object.prototype.hasOwnProperty.call(
					entryOptions,
					selectedFieldId as string
				)
			) {
				const fieldOption = entryOptions[
					selectedFieldId as string
				] as Record<string, unknown>;
				if (fieldOption.choices) {
					const inputName =
						String(objectType) + '_rule_value_' + String(ruleIndex);
					str = GetRuleValuesDropDown(
						fieldOption.choices,
						objectType,
						ruleIndex,
						selectedValue as string,
						inputName
					);
				}

				if (fieldOption.placeholder) {
					str = $(str as string).attr(
						'placeholder',
						String(fieldOption.placeholder)
					)[0].outerHTML;
				}
			}

			return str;
		}
	);

	/* Add change event to conditional logic field */
	$('#gfpdf_conditional_logic')
		.on('change', function () {
			if (window.gfpdf_current_pdf !== undefined) {
				/* Only set up a .conditionalLogic object if it doesn't exist */
				if (
					typeof window.gfpdf_current_pdf.conditionalLogic ===
						'undefined' &&
					$(this).prop('checked')
				) {
					window.gfpdf_current_pdf.conditionalLogic =
						new ConditionalLogic();
				} else if (!$(this).prop('checked')) {
					window.gfpdf_current_pdf.conditionalLogic = null;
				}
			}
			ToggleConditionalLogic(false, 'gfpdf');
		})
		.trigger('change');
}
