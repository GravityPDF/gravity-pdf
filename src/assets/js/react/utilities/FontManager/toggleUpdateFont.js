/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Toggle show or hide update font panel
 *
 * @param history: object
 * @param fontId: string
 *
 * @since 6.0
 */
export function toggleUpdateFont (history, fontId) {
  const editFontColumn = document.querySelector('.update-font')

  if (fontId) {
    const pathname = history.location.pathname

    if (pathname.substr(pathname.lastIndexOf('/') + 1) === fontId) {
      return removeClass(editFontColumn, history)
    }

    return addClass(editFontColumn, history, fontId)
  }

  return removeClass(editFontColumn, history)
}

export function removeClass (editFontColumn, history) {
  editFontColumn.classList.remove('show')

  /* Avoid Warning: Hash history cannot PUSH the same path */
  if (history.location.pathname === '/fontmanager/') {
    return
  }

  return history.push('/fontmanager/')
}

export function addClass (editFontColumn, history, fontId) {
  editFontColumn.classList.add('show')

  return history.push('/fontmanager/' + fontId)
}
