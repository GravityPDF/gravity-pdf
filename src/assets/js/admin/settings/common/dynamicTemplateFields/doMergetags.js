import $ from 'jquery'

/**
 * Remove any existing merge tags and reinitialise
 * @return void
 * @since 4.0
 */
export function doMergetags () {
  if (window.gfMergeTags && typeof form !== 'undefined' && $('.merge-tag-support').length >= 0) {
    $('#gfpdf-fieldset-gfpdf_form_settings_template .merge-tag-support').each(function () {
      $(this).wrap('<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag"></div>')
      new gfMergeTagsObj(form, $(this)) // eslint-disable-line
    })
  }
}
