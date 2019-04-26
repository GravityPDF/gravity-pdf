import $ from 'jquery'

/**
 * Remove any existing merge tags and reinitialise
 * @return void
 * @since 4.0
 */
export function doMergetags () {
  if (window.gfMergeTags && typeof form !== 'undefined' && $('.merge-tag-support').length >= 0) {
    $('.merge-tag-support').each(function () {
      new gfMergeTagsObj(form, $(this))
    })
  }
}
