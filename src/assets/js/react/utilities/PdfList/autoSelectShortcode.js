export function autoSelectShortcode (gfPdfListForm) {
  const shortcodeFields = Array.from(gfPdfListForm.querySelectorAll('td.column-shortcode'))

  if (shortcodeFields.length > 0) {
    shortcodeFields.map(field => {
      addEventListener(field)
    })
  }
}

export function addEventListener (field) {
  field.addEventListener('click', () => {
    document.execCommand('selectall', null, false)
    document.execCommand('copy')
  })
}
