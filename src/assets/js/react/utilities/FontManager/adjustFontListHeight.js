/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * This function is used to auto adjust font list column height to avoid
 * overlapping display of the update font panel
 *
 * @since 6.0
 */
export function adjustFontListHeight () {
  const fontListColumn = document.querySelector('.font-list-column')
  const updateFont = document.querySelector('.update-font.show')

  fontListColumn.style.height = window.getComputedStyle(updateFont).height
}
