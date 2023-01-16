/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Wrapper for the fetch() API which return a promise response
 *
 * @param url: string
 * @param init: object
 *
 * @returns Promise response
 *
 * @since 6.0
 */
export const api = async (url, init) => {
  const response = await window.fetch(url, init)

  return response
}
