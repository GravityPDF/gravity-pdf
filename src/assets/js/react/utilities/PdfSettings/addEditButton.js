export default function addEditButton (pdfSettingFieldSets, form) {
  const items = Array.from(pdfSettingFieldSets)
  /* Remove last element of the array */
  items.pop()

  items.map((fieldset, index) => {
    /* Check if fieldset is hidden */
    if (fieldset.style.display !== 'none') {
      const collapsibleToggleIcon = fieldset.querySelector('.gform-settings-panel__collapsible-toggle-checkbox')

      collapsibleToggleIcon.addEventListener('click', function () {
        insertAfter(fieldset, form, index)
      })

      insertAfter(fieldset, form, index, 'firstLoad')
    }
  })
}

export function insertAfter (fieldset, form, index, firstLoad) {
  const wrapperClass = 'submit-container-' + index

  if (!fieldset.classList.contains('gform-settings-panel--collapsed')) {
    const submitButton = form.querySelector('#submit')
    const submitButtonClone = submitButton.cloneNode(true)
    submitButtonClone.setAttribute('id', ('submit_' + index))

    const wrapper = document.createElement('div')
    wrapper.setAttribute('class', wrapperClass)
    wrapper.innerHTML = submitButtonClone.outerHTML

    return fieldset.parentNode.insertBefore(wrapper, fieldset.nextSibling)
  }

  if (firstLoad) {
    return
  }

  /* Remove button when fieldset collapsed */
  document.querySelector(`.${wrapperClass}`).remove()
}
