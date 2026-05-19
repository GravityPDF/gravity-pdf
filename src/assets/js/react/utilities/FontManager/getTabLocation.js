/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.14.3
 */

/**
 * Return the value of the final query-string parameter (after the last `=`).
 *
 * Gravity Forms admin URLs put the active "tab" in different params depending
 * on context: `subview=PDF` on the global settings page, `tab=tools` on the
 * Tools tab, and a form id at the end of form-level PDF settings. Reading the
 * value after the last `=` mirrors that order-dependent convention so callers
 * can distinguish global vs form vs Tools without parsing each variant.
 *
 * @returns {string} the trailing parameter value, or '' when the URL has no query string
 */
export function getTabLocation () {
  const { search } = window.location
  const lastEquals = search.lastIndexOf('=')

  return lastEquals === -1 ? '' : search.substring(lastEquals + 1)
}
