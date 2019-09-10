import $ from 'jquery'

export function spinner (classname) {
  let $spinner = $('<img alt=' + GFPDF.spinnerAlt + ' src=' + GFPDF.spinnerUrl + ' class=' + classname + ' />')
  return $spinner
}
