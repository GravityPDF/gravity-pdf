/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Clone action toolbar below each fieldset if it is enabled
 *
 * @param {Element} pdfSettingFieldSets
 * @param {Element} form
 */
export function actionToolbar (pdfSettingFieldSets, form) {
  const items = Array.from(pdfSettingFieldSets)
  /* Remove last element of the array */
  items.pop()

  items.map((fieldset, index) => {
    /* Check if fieldset is hidden */
    if (fieldset.style.display !== 'none') {
      const collapsibleToggleIcon = fieldset.querySelector('.gform-settings-panel__collapsible-toggle-checkbox')

      collapsibleToggleIcon.addEventListener('click', () => insertAfter(fieldset, form, index))

      return insertAfter(fieldset, form, index, 'firstLoad')
    }

    return false
  })
}

export function insertAfter (fieldset, form, index, firstLoad) {
  const wrapperClass = 'form-action-toolbar-' + index

  if (!fieldset.classList.contains('gform-settings-panel--collapsed')) {
    // get the original toolbar
    const actionToolbar = form.querySelector('.form-action-toolbar:last-of-type')
    const actionToolbarClone = actionToolbar.cloneNode(true)

    actionToolbarClone.classList.add(wrapperClass)

    return fieldset.parentNode.insertBefore(actionToolbarClone, fieldset.nextSibling)
  }

  if (firstLoad) {
    return
  }

  /* Remove button when fieldset collapsed */
  document.querySelector(`.${wrapperClass}`).remove()
}
