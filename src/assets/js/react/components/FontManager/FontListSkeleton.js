/* Dependencies */
import React from 'react'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font list loading skeleton UI
 *
 * @since 6.0
 */
const FontListSkeleton = () => {
  const fontList = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']

  return (
    <div data-test='component-FontListSkeleton' className='font-list-items-skeleton'>
      {fontList.map(font => (
        <div key={font} className='font-list-item'>
          <div>
            <span className='placeholder dashicons dashicons-trash' />
          </div>
          <span className='placeholder font-name' />
          <div>
            <span className='placeholder dashicons dashicons-yes' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
        </div>
      ))}
    </div>
  )
}

export default FontListSkeleton
