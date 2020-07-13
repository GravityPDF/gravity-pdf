import $ from 'jquery'

/**
 * Check if the template type is 'legacy' and hide the font type, size and colour, otherwise show those fields
 * @param type
 * @since 4.0
 */
export function toggleFontAppearance (type) {
  const $rows = $('#gfpdf-settings-field-wrapper-font, #gfpdf-settings-field-wrapper-font_size, #gfpdf-settings-field-wrapper-font_colour')

  /* Hide our font fields if processing a legacy template */
  if (type === 'legacy') {
    $rows.hide()
  } else { /* Ensure the fields are showing */
    $rows.show()
  }
}
