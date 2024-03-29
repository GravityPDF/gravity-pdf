/* Dependencies */
import request from 'superagent/dist/superagent.min'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Do AJAX call
 *
 * @param searchQuery
 *
 * @returns {{method.get}}
 *
 * @since 5.2
 */
export const apiGetSearchResult = searchQuery => {
  return request.get(`https://gravitypdf.com/wp-json/wp/v2/v6_docs/?search=${searchQuery}`)
}
