import $ from 'jquery'

/**
 * Show / Hide our custom paper size as needed
 * @return void
 * @since 4.0
 */
export function setupCustomPaperSize () {
  $('.gfpdf_paper_size').each(function () {
    const $customPaperSize = $(this).nextAll('.gfpdf_paper_size_other').first()
    const $paperSize = $(this).find('select')

    /* Add our change event */
    $paperSize.off('change').on('change', function () {
      if ($(this).val() === 'CUSTOM') {
        $customPaperSize.fadeIn()
      } else {
        $customPaperSize.fadeOut()
      }
    }).trigger('change')
  })
}
