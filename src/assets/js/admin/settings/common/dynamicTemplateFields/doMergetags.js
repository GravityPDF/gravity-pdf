/**
 * Reinitialise new merge tags
 *
 * @since 4.0
 */
export function doMergetags () {
  document.dispatchEvent(new Event('gform/merge_tag/initialize'))
}
