export function addEditButton(
	pdfSettingFieldSets: NodeListOf<HTMLElement>,
	form: HTMLElement
): void {
	const items = Array.from(pdfSettingFieldSets);
	/* Remove last element of the array */
	items.pop();

	items.map((fieldset, index) => {
		/* Check if fieldset is hidden */
		if (fieldset.style.display !== 'none') {
			const collapsibleToggleIcon = fieldset.querySelector(
				'.gform-settings-panel__collapsible-toggle-checkbox'
			);

			collapsibleToggleIcon?.addEventListener('click', () =>
				insertAfter(fieldset, form, index)
			);

			return insertAfter(fieldset, form, index, 'firstLoad');
		}

		return false;
	});
}

export function insertAfter(
	fieldset: HTMLElement,
	form: HTMLElement,
	index: number,
	firstLoad?: string
): Node | void {
	const wrapperClass = 'submit-container-' + index;

	if (!fieldset.classList.contains('gform-settings-panel--collapsed')) {
		const submitButton = form.querySelector<HTMLElement>('#submit');
		const submitButtonClone = submitButton?.cloneNode(true) as
			| HTMLElement
			| undefined;
		submitButtonClone?.setAttribute('id', 'submit_' + index);

		const wrapper = document.createElement('div');
		wrapper.setAttribute('class', wrapperClass);
		wrapper.innerHTML = submitButtonClone?.outerHTML ?? '';

		return (
			fieldset.parentNode?.insertBefore(wrapper, fieldset.nextSibling) ??
			undefined
		);
	}

	if (firstLoad) {
		return;
	}

	/* Remove button when fieldset collapsed */
	document.querySelector(`.${wrapperClass}`)?.remove();
}
