/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * Contains stateless React components for our Single Template
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Stateless Component
 *
 * Display the current template label
 *
 * @since 4.1
 */
export const CurrentTemplate = ({ isCurrentTemplate, label }) => {
  return (isCurrentTemplate) ? (
    <span
      data-test='component-currentTemplate'
      className='current-label'
    >
      {label}
    </span>
  ) : (
    <span />
  )
}

CurrentTemplate.propTypes = {
  isCurrentTemplate: PropTypes.bool,
  label: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template name and version number
 *
 * @since 4.1
 */
export const Name = ({ name, version, versionLabel }) => (
  <h2
    data-test='component-name'
    className='theme-name'
  >
    {name}

    <Version version={version} label={versionLabel} />
  </h2>
)

Name.propTypes = {
  name: PropTypes.string,
  version: PropTypes.string,
  versionLabel: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template version number
 *
 * @since 4.1
 */
export const Version = ({ label, version }) => {
  return (version) ? (
    <span data-test='component-version' className='theme-version'>{label}: {version}</span>
  ) : (
    <span />
  )
}

Version.propTypes = {
  label: PropTypes.string,
  version: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template author (and link to website, if any)
 *
 * @since 4.1
 */
export const Author = ({ author, uri }) => {
  if (uri) {
    return (
      <p data-test='component-author' className='theme-author'>
        <a href={uri}>
          {author}
        </a>
      </p>
    )
  } else {
    return (
      <p data-test='component-author' className='theme-author'>
        {author}
      </p>
    )
  }
}

Author.propTypes = {
  author: PropTypes.string,
  uri: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template group
 *
 * @since 4.1
 */
export const Group = ({ label, group }) => (
  <p data-test='component-group' className='theme-author'>
    <strong>{label}: {group}</strong>
  </p>
)

Group.propTypes = {
  label: PropTypes.string,
  group: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template description
 *
 * @since 4.1
 */
export const Description = ({ desc }) => (
  <p data-test='component-description' className='theme-description'>
    {desc}
  </p>
)

Description.propTypes = {
  desc: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template tags
 *
 * @since 4.1
 */
export const Tags = ({ label, tags }) => {
  return (tags) ? (
    <p data-test='component-tags' className='theme-tags'>
      <span>{label}:</span> {tags}
    </p>
  ) : (
    <span />
  )
}

Tags.propTypes = {
  label: PropTypes.string,
  tags: PropTypes.string
}
