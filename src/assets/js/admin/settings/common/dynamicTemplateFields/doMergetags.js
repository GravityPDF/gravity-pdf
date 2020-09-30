import $ from 'jquery'

/**
 * Remove any existing merge tags and reinitialise
 * @return void
 * @since 4.0
 */
export function doMergetags () {
  if (window.gfMergeTags && typeof form !== 'undefined' && $('#gfpdf-fieldset-gfpdf_form_settings_template .merge-tag-support').length >= 0) {
    /* Initialise */
    $('#gfpdf-fieldset-gfpdf_form_settings_template .merge-tag-support').each(function () {
      new gfMergeTagsObj(form, $(this)) // eslint-disable-line
    })

    /* Wrap merge tag selectors with new GF2.5 markup */
    $('#gfpdf-fieldset-gfpdf_form_settings_template .gform-settings-field').each(function () {
      $(this)
        .find('.merge-tag-support, .merge-tag-support + span')
        .wrapAll('<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag"></div>')

      $(this)
        .find('.all-merge-tags.textarea')
        .parent()
        .wrapAll('<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag gfpdf-merge-tag-container"></div>')
    })
  }
}
