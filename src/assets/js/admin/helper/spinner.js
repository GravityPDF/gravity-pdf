import $ from 'jquery'

export function spinner (classname) {
  return $('<img />')
    .attr('alt', GFPDF.spinnerAlt)
    .attr('src', GFPDF.spinnerUrl)
    .addClass(classname)
}
