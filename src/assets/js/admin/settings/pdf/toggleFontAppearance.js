import $ from 'jquery'

/**
 * Check if the template type is 'legacy' and hide the font type, size and colour, otherwise show those fields
 * @param type
 * @since 4.0
 */
export function toggleFontAppearance (type) {
  const $rows = $('#pdf-general-appearance').find('tr.gfpdf_font_type, tr.gfpdf_font_size, tr.gfpdf_font_colour')

  /* Hide our font fields if processing a legacy template */
  if (type === 'legacy') {
    $rows.hide()
  } else { /* Ensure the fields are showing */
    $rows.show()
  }
}
