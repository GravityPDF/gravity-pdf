/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 * A simple function that forces a promise to resolve, returning an object that indicates the actual promise status
 * and the underlying object returned from the original promise.
 *
 * See https://www.npmjs.com/package/promise-reflect for usage
 */
export default (promise) => {
  return promise
    .then(data => {
      return {data: data, status: 'resolved'}
    })
    .catch(error => {
      return {error: error, status: 'rejected'}
    })
}