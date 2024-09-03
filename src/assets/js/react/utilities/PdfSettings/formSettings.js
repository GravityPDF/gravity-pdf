import $ from 'jquery'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Prepare form inputs for use with Gravity PDF Form Settings API
 * @returns {FormData}
 *
 * @since 6.12
 */
export function getCurrentPdfSettingsForApi (schema) {
  let formData = new FormData()

  // loop over the schema and get the values
  for (const [key, property] of Object.entries(schema.properties)) {
    // ignore readonly fields
    if (property.readonly) {
      continue
    }

    // if not matching DOM node, skip
    const propertyNodes = document.querySelectorAll('[name^="gfpdf_settings[' + key + ']"]')
    if (propertyNodes.length === 0) {
      continue
    }

    // loop over matching HTML tags
    for (const input of propertyNodes.values()) {
      // add field data based on schema and input types
      switch (property.type) {
        case 'boolean':
          formData.set(key, input.checked)
          break

        case 'array':
          // handle individual checkboxes
          if (input.type === 'checkbox' && input.checked) {
            formData.append(key + '[]', input.value)
          }

          // Add all checked multiselect options
          if (input.nodeName === 'SELECT' && input.multiple) {
            for (const option of input.querySelectorAll('option:checked').values()) {
              formData.append(key + '[]', option.value)
            }
          }
          break

        case 'number':
        case 'integer':
          formData.set(key, input.value)

          break

        case 'string':

          // skip checkbox or radio fields that are not checked
          if (['checkbox', 'radio'].includes(input.type) && !input.checked) {
            continue
          }

          // if field should be a hex color and is empty
          if (property.format === 'hex-color' && input.value.length === 0) {
            continue
          }

          formData.set(key, input.value)

          break
      }
    }
  }

  /**
   * Manipulate the formData before sending to the PDF Preview API
   *
   * @param {FormData} formData The constructed Previewer API PDF settings
   * @param {object} schema Valid Preview API schema for the current template
   *
   * @since 6.12
   */
  formData = gform.applyFilters('gfpdf_preview_settings', formData, schema)

  return formData
}

/**
 * Handle custom Paper Size for Preview API
 */
gform.addFilter('gfpdf_preview_settings', (formData, schema) => {
  // Fix custom paper size (the API uses a structured object instead of an array)
  if (!schema.properties.custom_pdf_size || formData.get('pdf_size') !== 'CUSTOM') {
    return formData
  }

  formData.set('custom_pdf_size[width]', document.getElementById('gfpdf_settings[custom_pdf_size]_width').value)
  formData.set('custom_pdf_size[height]', document.getElementById('gfpdf_settings[custom_pdf_size]_height').value)
  formData.set('custom_pdf_size[unit]', document.getElementById('gfpdf_settings[custom_pdf_size]_measurement')
    .value
    .replace('millimeters', 'mm')
    .replace('inches', 'in'))

  return formData
})

/**
 * Turn off conditional logic for PDF preview
 */
gform.addFilter('gfpdf_preview_settings', (formData, schema) => {
  formData.delete('conditional')
  formData.delete('conditionalLogic')

  return formData
})

/**
 * Unset the Label / Filename if empty for PDF Preview
 * The default value set in the schema will be used instead
 */
gform.addFilter('gfpdf_preview_settings', (formData, schema) => {
  if (formData.get('name') === '') {
    formData.delete('name')
  }

  if (formData.get('filename') === '') {
    formData.delete('filename')
  }

  return formData
})

/**
 * Trigger the submit events on the form so fields can save their data
 * but cancel the event before the browser actually posts the form data
 *
 * @param formId
 *
 * @since 6.12
 */
export function triggerFakeFormSubmit (formId) {
  const form = document.getElementById(formId)
  const formListener = (event) => event.preventDefault()

  form.addEventListener('submit', formListener)

  // trigger native submit and jQuery submit (two independent event systems)
  $('#' + formId).trigger('submit')
  form.requestSubmit()

  form.removeEventListener('submit', formListener)
}
