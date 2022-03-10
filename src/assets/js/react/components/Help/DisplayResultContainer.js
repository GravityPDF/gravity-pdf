/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Displays result based on Search Input value
 *
 * @param groups (object)
 *
 * @since 5.2
 */
const DisplayResultContainer = ({ groups }) => {
  return (
    <div className='search-result'>
      {
        Object.entries(groups).map((item, index) => (
          <section className='group-section' key={index}>
            <div className='group-name'>{item[0]}</div>

            <ul className='search-menu'>
              {item[1].map((item, index) => {
                const url = item[1]
                /* Additional content description */
                const content = item[2]
                const hierarchyLvl2 = item[0].lvl2 ? item[0].lvl2 : ''
                const hierarchyLvl3 = item[0].lvl3 ? (' - ' + item[0].lvl3) : ''
                const combinedHierarchy = hierarchyLvl2 + hierarchyLvl3
                const additionalInfo = content ? (content.substr(0, 80) + '...') : combinedHierarchy

                return (
                  <li key={index}>
                    <a href={url}>
                      <div className='hit-container'>
                        <div className='hit-icon'>
                          <svg
                            width='20'
                            height='20'
                            viewBox='0 0 20 20'
                          >
                            <path
                              d='M17 6v12c0 .52-.2 1-1 1H4c-.7 0-1-.33-1-1V2c0-.55.42-1 1-1h8l5 5zM14 8h-3.13c-.51 0-.87-.34-.87-.87V4'
                              stroke='currentColor'
                              fill='none'
                              fillRule='evenodd'
                              strokeLinejoin='round'
                            />
                          </svg>
                        </div>
                        <div className='hit-content-wrapper'>
                          <span className='hit-title' dangerouslySetInnerHTML={{ __html: item[0].lvl1 }} />
                          <span className='hit-path' dangerouslySetInnerHTML={{ __html: additionalInfo }} />
                        </div>
                        <div className='hit-action'>
                          <svg
                            className='DocSearch-Hit-Select-Icon'
                            width='20'
                            height='20'
                            viewBox='0 0 20 20'
                          >
                            <g
                              stroke='currentColor'
                              fill='none'
                              fillRule='evenodd'
                              strokeLinecap='round'
                              strokeLinejoin='round'
                            >
                              <path d='M18 3v4c0 2-2 4-4 4H2' />
                              <path d='M8 17l-6-6 6-6' />
                            </g>
                          </svg>
                        </div>
                      </div>
                    </a>
                  </li>
                )
              })}
            </ul>
          </section>
        ))
      }
    </div>
  )
}

/**
 *
 * @since 5.2
 */
DisplayResultContainer.propTypes = {
  groups: PropTypes.object.isRequired
}

export default DisplayResultContainer
