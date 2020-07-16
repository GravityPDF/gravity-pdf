import $ from 'jquery'

export function spinner (classname) {
  const $spinner = $('<img alt=' + GFPDF.spinnerAlt + ' src=' + GFPDF.spinnerUrl + ' class=' + classname + ' />')
  return $spinner
}
